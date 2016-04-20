<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    exclude-result-prefixes="xs"
    version="2.0">
    
    <!-- replace Engraver with Lithographer when physicalDescription/form = Lithographs -->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="name[@displayLabel='Engraver/lithographer']">
        <xsl:choose>
            <xsl:when test="//physicalDescription/form = 'Lithographs'">
                <name displayLabel="Engraver/lithographer">
                    <role>
                        <roleTerm type="code" authority="marcrelator">ltg</roleTerm>
                        <roleTerm type="text" authority="marcrelator">Lithographer</roleTerm>
                    </role>
                    <namePart>
                        <xsl:value-of select="namePart"/>
                    </namePart>
                </name>
            </xsl:when>
            <xsl:otherwise>
                <name>
                    <xsl:apply-templates />
                </name>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>