<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
      xmlns:xs="http://www.w3.org/2001/XMLSchema"
      xmlns:mods="http://www.loc.gov/mods/v3"
      xpath-default-namespace="http://www.loc.gov/mods/v3"
      exclude-result-prefixes="xs"
      version="2.0"
      xmlns="http://www.loc.gov/mods/v3">
    
    <!-- splits internetMediaType "mp4: 16mm film" into internetMediaType and note, or lowercase PDF, specific for Loyola Athletic collection-->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:variable name="targetText" select="node()/physicalDescription/internetMediaType/text()"/>
    <xsl:variable name="myRegEx" select="'([0-9a-zA-Z\s,]+);\s?([0-9\sa-zA-Z.&quot;]+)'"/>
    
    <xsl:template match="physicalDescription/internetMediaType">
        <xsl:choose>
            <xsl:when test="matches(., ';')">
                <xsl:analyze-string select="$targetText" regex="{$myRegEx}">
                    <xsl:matching-substring>
                        <internetMediaType>
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </internetMediaType>
                        <note type="content">
                            <xsl:value-of select="replace(regex-group(2), '\s+', ' ')"/>
                        </note>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:when test="matches(., 'PDF')">
                <internetMediaType>pdf</internetMediaType>
            </xsl:when>
            <xsl:otherwise>
                <internetMediaType>
                    <xsl:value-of select="."/>
                </internetMediaType>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>