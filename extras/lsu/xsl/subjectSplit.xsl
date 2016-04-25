<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3" >

    <!-- changes subject topic topic to subject topic subject topic (split on double dashes)
     capitalizes first letter and remove period if it ends the string; splits subject/geographic-->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
   
    <xsl:template match="subject[topic]">
        <xsl:for-each select="topic">
            <xsl:element name="subject">
                <xsl:attribute name="authority">lcsh</xsl:attribute>
                    <xsl:for-each select="tokenize(.,'--')">
                        <xsl:element name="topic">
                            <xsl:value-of select="replace(replace(concat(upper-case(substring(.,1,1)),substring(.,2)), '^\s+|\s+$', ''),'\.$','')"/>
                        </xsl:element>
                    </xsl:for-each>
            </xsl:element>
        </xsl:for-each>
    </xsl:template>
    
    <xsl:template match="subject[geographic]">
        <xsl:for-each select="geographic">
            <xsl:element name="subject">
                <xsl:attribute name="authority">local</xsl:attribute>
                <xsl:for-each select="tokenize(.,'--')">
                    <xsl:element name="geographic">
                        <xsl:value-of select="replace(replace(concat(upper-case(substring(.,1,1)),substring(.,2)), '^\s+|\s+$', ''),'\.$','')"/>
                    </xsl:element>
                </xsl:for-each>
            </xsl:element>
        </xsl:for-each>
    </xsl:template>

</xsl:stylesheet>