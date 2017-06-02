<xsl:stylesheet version="1.0"  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:php="http://php.net/xsl" xsl:extension-element-prefixes="php" exclude-result-prefixes="xsl xsi php">

 <!-- XSLT stylesheet to convert the output of the 'tree' utility (or equivalent)
      into XML that represents the structure of an Islandora compound object. Called
      by the create_structure_files.php script that is part of the Islandora Compound
      Batch module. -->

  <xsl:output method="xml" encoding="utf-8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:template match="tree/directory">
  <xsl:comment>Islandora compound structure file used by the Compound Batch module. On batch ingest,
    'islandora_compound_object' elements become compound objects, and 'child' elements become their
    children. Files in directories named in child elements' 'content' attribute will be added as their
    datastreams. If 'islandora_compound_object' elements do not contain a MODS.xml file, the value of
    the 'title' attribute will be used as the parent's title/label.</xsl:comment>

    <islandora_compound_object >
      <xsl:attribute name="title">
        <xsl:value-of select="php:function('get_dir_name')" />
      </xsl:attribute>
      <xsl:apply-templates/>
   </islandora_compound_object>
  </xsl:template>

  <!-- We aren't intersted in these nodes, so apply an empty template to them. -->
  <xsl:template match="report|directories|files"/>

  <xsl:template match="directory">
    <xsl:choose>
      <xsl:when test="count(file) > 1">
        <child content="{./@name}"/>
          <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <parent title="{./@name}">
          <xsl:apply-templates/>
        </parent>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
