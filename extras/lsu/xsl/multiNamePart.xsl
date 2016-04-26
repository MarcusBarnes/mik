<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3" >
    
    <!-- If the namePart is blank, or is "Unknown" then delete the name node -->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="name[@displayLabel='Contributor']">
        <xsl:for-each select="namePart">
            <name displayLabel="Contributor">
                <role>
                    <roleTerm type="text" authority="marcrelator">Contributor</roleTerm>
                    <roleTerm type="code" authority="marcrelator">ctb</roleTerm>
                </role>
                <namePart>
                    <xsl:value-of select="."/>
                </namePart>
            </name>
        </xsl:for-each>
    </xsl:template>
</xsl:stylesheet>