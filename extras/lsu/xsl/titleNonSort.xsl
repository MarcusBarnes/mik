<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3" >
    
    <!-- If the first word of the title node is "A", "An" or "The" then it wraps it in <nonSort> -->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="title">
        <xsl:variable name="firstWord" select="substring-before(concat(normalize-space(.), ' '),' ')"/>
        <xsl:variable name="restOfTitle" select="substring-after(normalize-space(.),' ')"/>
        <xsl:choose>
            <xsl:when test="$firstWord='The'">
                
                <nonSort>
                    <xsl:value-of select="$firstWord"/>
                </nonSort>
                <title>
                    <xsl:value-of select="$restOfTitle"/>
                </title>    
            </xsl:when>
            <xsl:when test="$firstWord='An'">
                <nonSort>
                    <xsl:value-of select="$firstWord"/>
                </nonSort>
                <title>
                    <xsl:value-of select="$restOfTitle"/>
                </title>   
            </xsl:when>
            <xsl:when test="$firstWord='A'">
                <nonSort>
                    <xsl:value-of select="$firstWord"/>
                </nonSort>
                <title>
                    <xsl:value-of select="$restOfTitle"/>
                </title>   
            </xsl:when>
            <xsl:otherwise>
                <title>
                <xsl:value-of select="." />
                </title>    
            </xsl:otherwise>
        </xsl:choose>

    </xsl:template>
</xsl:stylesheet>