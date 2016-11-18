<xsl:stylesheet version="1.0"  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:php="http://php.net/xsl" xsl:extension-element-prefixes="php" exclude-result-prefixes="dc oai_dc php" >

<!-- XSLT stylesheet to transform from OAI DC to MODS. -->

<xsl:output method="xml" encoding="utf-8" indent="yes"/>

<xsl:template match="oai_dc:dc">
  <mods version="3.0" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd">
    <xsl:apply-templates/>
  </mods>
</xsl:template>

<xsl:template match="dc:title">
  <titleInfo>
    <title><xsl:value-of select="."/></title>
  </titleInfo>
</xsl:template>

<xsl:template match="dc:subject">
  <subject>
    <topic><xsl:value-of select="."/></topic>
  </subject>
</xsl:template>

<!-- gets rid of the extra dc:description field on KORA objects -->
<xsl:template match="dc:description[1]">
  <abstract><xsl:value-of select="."/></abstract>
</xsl:template>
<xsl:template match="dc:description[2]" />


<!-- Name splitting processing template. Name splitting for dc:creator and dc:contributor assumes any name with comma is personal, without is corporate. -->

<xsl:variable name="delimiter">
  <xsl:text>, </xsl:text>
</xsl:variable>

<xsl:template name="processingTemplate">
  <xsl:param name="nameList"/>
  <xsl:param name="nameRole"/>
    <xsl:choose>
      <xsl:when test="contains($nameList,$delimiter)  ">
        <xsl:element name="name"><xsl:attribute name="type">personal</xsl:attribute> 
          <xsl:element name="namePart"><xsl:attribute name="type">family</xsl:attribute>
            <xsl:value-of select="substring-before($nameList,$delimiter)"/>
          </xsl:element>
          <xsl:element name="namePart"><xsl:attribute name="type">given</xsl:attribute>
            <xsl:value-of select="substring-after($nameList,$delimiter)"/>
          </xsl:element>

          <xsl:element name="role">
            <xsl:element name="roleTerm"><xsl:attribute name="authority">marcrelator</xsl:attribute><xsl:attribute name="type">text</xsl:attribute>
              <xsl:value-of select="$nameRole" /></xsl:element>
          </xsl:element>
        <xsl:if test="contains($nameRole,'creator')">
          <xsl:element name="role">
            <xsl:element name="roleTerm"><xsl:attribute name="authority">marcrelator</xsl:attribute><xsl:attribute name="type">text</xsl:attribute>author</xsl:element>
          </xsl:element>
		</xsl:if>

		</xsl:element>
      </xsl:when>
      <xsl:when test="string-length($nameList)=1">
        <xsl:element name="namePart">
          <xsl:value-of select="$nameList"/>
        </xsl:element>
      </xsl:when> 
  </xsl:choose>    
</xsl:template>

<!-- Looks at whether dc:creator contains a comma or not - if comma, apply the split processing template and name type=personal.
     Otherwise, no split and name type=corporate. -->

<xsl:template match="dc:creator">
  <xsl:variable name="nameList"><xsl:value-of select="."/></xsl:variable>
  <xsl:variable name="nameRole">creator</xsl:variable>
  <xsl:choose>
    <xsl:when test="contains($nameList, ',')">
      <xsl:call-template name="processingTemplate">
        <xsl:with-param name="nameList" select="$nameList"/>
        <xsl:with-param name="nameRole" select="$nameRole"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <name type="corporate">
        <namePart><xsl:value-of select="."/></namePart>
          <role>
            <roleTerm authority="marcrelator" type="text">author</roleTerm>
          </role>
          <role>
            <roleTerm authority="marcrelator" type="text">creator</roleTerm>
          </role>
      </name>
    </xsl:otherwise> 
  </xsl:choose>
</xsl:template>

<!-- There is no actual dc:contributor in our whole metadata set!! Is this because everyone is creator? Is this a problem that KPU should look to correct? -->
<xsl:template match="dc:contributor">
  <xsl:variable name="nameList"><xsl:value-of select="."/></xsl:variable>
  <xsl:variable name="nameRole">contributor</xsl:variable>
  <xsl:choose>
    <xsl:when test="contains($nameList, ',')">
      <xsl:call-template name="processingTemplate">
        <xsl:with-param name="nameList" select="$nameList"/>
        <xsl:with-param name="nameRole" select="$nameRole"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <name type="corporate">
        <namePart><xsl:value-of select="."/></namePart>
        <role>
          <roleTerm authority="marcrelator" type="text">contributor</roleTerm>
        </role>
      </name>
    </xsl:otherwise> 
  </xsl:choose>
</xsl:template>

<xsl:template match="dc:publisher">
  <originInfo>
    <publisher><xsl:value-of select="."/></publisher>
  </originInfo>
</xsl:template>

<!-- dc:date is too general to map to specific MODS elements. Use dateIssued for now. -->
<xsl:template match="dc:date">
  <originInfo>
    <dateIssued><xsl:value-of select="php:function('trim_date', string(.))"/></dateIssued>
  </originInfo>
</xsl:template>

<!-- Note that while Kwantlen's dc:type looks to map to typeOfResource, EVERY file appears to use "text", even when it's an image. -->
<xsl:template match="dc:type"> 
  <typeOfResource><xsl:value-of select="."/></typeOfResource>
</xsl:template>

<xsl:template match="dc:identifier[1]">
    <identifier type="kora_legacy_url"><xsl:value-of select="."/></identifier>
</xsl:template>
<xsl:template match="dc:identifier[2]">
    <identifier type="kora_old_filepath"><xsl:value-of select="."/></identifier>
</xsl:template>

<xsl:template match="dc:source">
  <relatedItem type="original"><xsl:value-of select="."/></relatedItem>
</xsl:template>

<xsl:template match="dc:language">
  <language><languageTerm type="text"><xsl:value-of select="."/></languageTerm></language>
</xsl:template>

<xsl:template match="dc:rights">
  <accessCondition type="use and reproduction"><xsl:value-of select="."/></accessCondition>
</xsl:template>

<!-- dc:format is ambiguous, since it could map to one of physicalDescription/internetMediaType
     or physicalDescription/extent.  
     Brandon: Looks like all the entries under dc:format for KPU are MIME types, so probably physicalDescription/internetMediaType is best. -->
<xsl:template match="dc:format">
  <physicalDescription>
    <internetMediaType><xsl:value-of select="."/></internetMediaType>
  </physicalDescription>
</xsl:template>

<!-- dc:relation is too general to map to the detailed subelements of mods:relatedItem. -->
<!-- Brandon: There are no KORA objects that use this field anyway. -->

<!-- dc:coverage is ambiguous, since it could map to subject/geographic or subject/temporal. -->
<!-- Brandon: There are no KORA objects that use this field anyway. -->
</xsl:stylesheet>
