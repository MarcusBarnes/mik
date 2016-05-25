<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3" >
    
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="mods" exclude-result-prefixes="#all">
        <xsl:copy>
            <xsl:apply-templates select="titleInfo" />
            <xsl:apply-templates select="part" />
            <xsl:apply-templates select="name" />
            <xsl:apply-templates select="originInfo" />
            <xsl:apply-templates select="subject" />
            <xsl:apply-templates select="abstract" />
            <xsl:apply-templates select="note[@type='content']"/>
            <xsl:apply-templates select="typeOfResource" />
            <xsl:apply-templates select="physicalDescription" />
            <xsl:apply-templates select="genre" />
            <xsl:apply-templates select="note[@type='system details']"/>
            <xsl:apply-templates select="language" />
            <xsl:apply-templates select="note[@type='ownership']" />
            <xsl:apply-templates select="relatedItem" />
            <xsl:apply-templates select="location" />
            <xsl:apply-templates select="accessCondition" />
            <xsl:apply-templates select="note[@type='preferred citation']"/>
            <xsl:apply-templates select="identifier" />
            <xsl:apply-templates select="recordInfo" />
            <xsl:apply-templates select="extension" />
        </xsl:copy>
    </xsl:template>
        
</xsl:stylesheet>