<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xpath-default-namespace="http://www.loc.gov/mods/v3"
    exclude-result-prefixes="xs"
    version="2.0"
    xmlns="http://www.loc.gov/mods/v3">
    
    <!-- takes various permutations of date formatting in dateIssued and converts them
    YYYY-YYYY breaks into point=start and point=end
    Ca. YYYY gets an attribute qualifier="approximate"
    Ca. YYYY-YYYY gets approximate qualifier and start and end
    Before YYYY and YYYY gets split with point start and point end
    all others stay the same-->
    
    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:variable name="dates" select="node()/originInfo/dateIssued/text()"/>
    <xsl:variable name="yearRangeRegEx" select="'([0-9]{4})-([0-9]{4})'"/> <!-- YYYY-YYYY -->
    <xsl:variable name="caRegEx" select="'[cC]a.\s([0-9]{4})'"/> <!-- Ca. YYYY -->
    <xsl:variable name="betweenRegEx" select="'Between\s([0-9]{4})\sand\s([0-9]{4})'"/>
    <xsl:variable name="semicolonRegEx" select="'(^[0-9]{4});.*([0-9]{4}$)'"/>
    
    <xsl:template match="originInfo/dateIssued">
        <xsl:choose>
            <xsl:when test="matches(., $yearRangeRegEx) and not(matches(., 'Ca.'))">
                <xsl:analyze-string select="$dates" regex="{$yearRangeRegEx}">
                    <xsl:matching-substring>
                        <dateIssued point="start" keydate="yes">
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </dateIssued>
                        <dateIssued point="end" keydate="yes">
                            <xsl:value-of select="replace(regex-group(2), '\s+', ' ')"/>
                        </dateIssued>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:when test="matches(., $caRegEx) and not(matches(., '-'))">
                <xsl:analyze-string select="$dates" regex="{$caRegEx}">
                    <xsl:matching-substring>
                        <dateIssued point="start" keydate="yes" qualifier="approximate">
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </dateIssued>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:when test="matches(., '-') and matches(., 'Ca.')">
                <xsl:analyze-string select="$dates" regex="{$yearRangeRegEx}">
                    <xsl:matching-substring>
                        <dateIssued point="start" keydate="yes" qualifier="approximate">
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </dateIssued>
                        <dateIssued point="end" keydate="yes" qualifier="approximate">
                            <xsl:value-of select="replace(regex-group(2), '\s+', ' ')"/>
                        </dateIssued>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:when test="matches(., $betweenRegEx)">
                <xsl:analyze-string select="$dates" regex="{$betweenRegEx}">
                    <xsl:matching-substring>
                        <dateIssued point="start" keydate="yes">
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </dateIssued>
                        <dateIssued point="end" keydate="yes">
                            <xsl:value-of select="replace(regex-group(2), '\s+', ' ')"/>
                        </dateIssued>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:when test="matches(., $semicolonRegEx)">
                <xsl:analyze-string select="$dates" regex="{$semicolonRegEx}">
                    <xsl:matching-substring>
                        <dateIssued point="start" keydate="yes">
                            <xsl:value-of select="replace(regex-group(1), '\s+', ' ')"/>
                        </dateIssued>
                        <dateIssued point="end" keydate="yes">
                            <xsl:value-of select="replace(regex-group(2), '\s+', ' ')"/>
                        </dateIssued>
                    </xsl:matching-substring>
                </xsl:analyze-string>
            </xsl:when>
            <xsl:otherwise>
                <dateIssued keydate="yes">
                    <xsl:value-of select="."/>
                </dateIssued>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>