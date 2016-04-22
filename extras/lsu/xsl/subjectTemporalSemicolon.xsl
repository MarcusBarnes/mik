<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3" >

    <!-- changes subject topic topic to subject topic subject topic (split on double dashes)
     now with additional function to capitalize first letter-->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
   
    <xsl:template match="subject/temporal">
        <xsl:variable name="temporalText" select="."/>
        <temporal>
            <xsl:value-of select="replace($temporalText,'; ','')"/>
        </temporal>
    </xsl:template>

</xsl:stylesheet>