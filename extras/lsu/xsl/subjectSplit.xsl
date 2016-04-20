<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns="http://www.loc.gov/mods/v3" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    exclude-result-prefixes="Namespaces"
    version="2.0">
    
    <!-- 1. removes empty subject with displayLabel of Current common name 
    2. changes subject topic topic to subject topic subject topic (split on double dashes) -->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="subject">
        <xsl:choose>
            <xsl:when test="topic = ''">
            </xsl:when>
            <xsl:otherwise>
                <name displayLabel="Current common name">
                    <xsl:apply-templates />
                </name>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match="subject[topic]">
        <xsl:for-each select="topic">
            <xsl:element name="subject">
                <xsl:for-each select="tokenize(.,'--')">
                    <xsl:element name="topic">
                        <xsl:attribute name="authority">lcsh</xsl:attribute>
                        <xsl:value-of select="replace(., '^\s+|\s+$', '')"/>
                    </xsl:element>
                </xsl:for-each>
            </xsl:element>
        </xsl:for-each>
    </xsl:template>

</xsl:stylesheet>