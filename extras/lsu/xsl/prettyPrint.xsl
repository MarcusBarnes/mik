<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    exclude-result-prefixes="xs"
    version="2.0">
    <xsl:strip-space elements="*"/>
    <xsl:output method="xml" indent="yes" />
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <!-- and now for the namespaces on the root element (mods) -->
    
    <xsl:template match="*" priority="1">
        <xsl:element name="{local-name()}" namespace="http://www.loc.gov/mods/v3">
            <xsl:namespace name="xsi" select="'http://www.w3.org/2001/XMLSchema-instance'"/>
            <xsl:namespace name="mods" select="'http://www.loc.gov/mods/v3'"/>
            <xsl:namespace name="xlink" select="'http://www.w3.org/1999/xlink'"/>
            <xsl:namespace name="schemaLocation" select="'http://www.loc.gov/standards/mods/v3/mods-3-6.xsd'"/>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:element>
    </xsl:template>
</xsl:stylesheet>