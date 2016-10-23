<xsl:stylesheet version="1.0"  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
  exclude-result-prefixes="dc oai_dc" >

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

<xsl:template match="dc:description">
  <abstract type="description"><xsl:value-of select="."/></abstract>
</xsl:template>

<xsl:template match="dc:creator">
  <name>
    <namePart><xsl:value-of select="."/></namePart>
    <role>
      <roleTerm type="text">creator</roleTerm>
    </role>
  </name>
</xsl:template>

<xsl:template match="dc:contributor">
  <name>
    <namePart><xsl:value-of select="."/></namePart>
    <role>
      <roleTerm type="text">contributor</roleTerm>
    </role>
  </name>
</xsl:template>

<xsl:template match="dc:publisher">
  <originInfo>
    <publisher><xsl:value-of select="."/></publisher>
  </originInfo>
</xsl:template>

<!-- dc:date is too general to map to specific MODS elements. Use dateIssued for now. -->
<xsl:template match="dc:date">
  <originInfo>
    <dateIssued><xsl:value-of select="."/></dateIssued>
  </originInfo>
</xsl:template>

<xsl:template match="dc:type">
  <genre><xsl:value-of select="."/></genre>
</xsl:template>

<xsl:template match="dc:identifier">
  <identifier><xsl:value-of select="."/></identifier>
</xsl:template>

<xsl:template match="dc:source">
  <relatedItem type="original"><xsl:value-of select="."/></relatedItem>
</xsl:template>

<xsl:template match="dc:language">
  <language><xsl:value-of select="."/></language>
</xsl:template>

<xsl:template match="dc:rights">
  <accessCondition type="use and reproduction"><xsl:value-of select="."/></accessCondition>
</xsl:template>

<!-- dc:format is ambiguous, since it could map to one of physicalDescription/internetMediaType
     or physicalDescription/extent.  -->

<!-- dc:relation is too general to map to the detailed subelements of mods:relatedItem. -->

<!-- dc:coverage is ambiguous, since it could map to subject/geographic or subject/temporal. -->

</xsl:stylesheet>
