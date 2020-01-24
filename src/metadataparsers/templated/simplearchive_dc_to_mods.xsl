<?xml version="1.0" encoding="UTF-8" ?>

<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
	<xsl:output omit-xml-declaration="yes" method="xml" indent="yes" />
	
	<xsl:template match="/dublin_core">
        <mods  xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			<xsl:for-each select="dcvalue[@element='title']">
			<titleInfo>
				<title><xsl:value-of select="./text()" /></title>
			</titleInfo>
			</xsl:for-each>

			<xsl:for-each select="dcvalue[@element='contributor' and @qualifier='author']">
			<name>
				<role>
					<roleTerm type="text"><xsl:value-of select="@qualifier" /></roleTerm>
				</role>
				<namePart><xsl:value-of select="./text()" /></namePart>
			</name>
			</xsl:for-each>

			<originInfo>
				<xsl:for-each select="dcvalue[@element='publisher' and @qualifier='none']">
				  <publisher><xsl:value-of select="./text()" /></publisher>
				</xsl:for-each>
			    <xsl:for-each select="dcvalue[@element='date' and @qualifier='issued']">
				<dateIssued encoding="w3cdtf" keyDate="yes">
					<xsl:value-of select="./text()" />
				</dateIssued>
			    </xsl:for-each>
			    <xsl:for-each select="dcvalue[@element='date' and @qualifier='accessioned']">
				<dateCaptured encoding="iso8601">
					<xsl:value-of select="./text()" />
				</dateCaptured>
			    </xsl:for-each>
			</originInfo>

			<xsl:for-each select="dcvalue[@element='identifier' and @qualifier='issn']">
			<identifier>
				<xsl:attribute name="type">
					<xsl:value-of select="@qualifier"></xsl:value-of>
				</xsl:attribute>
				<xsl:value-of select="./text()" />
			</identifier>
			</xsl:for-each>
			<xsl:for-each select="dcvalue[@element='identifier' and @qualifier='uri']">
			<identifier>
				<xsl:attribute name="type">
					<xsl:value-of select="@qualifier"></xsl:value-of>
				</xsl:attribute>
				<xsl:value-of select="./text()" />
			</identifier>
			</xsl:for-each>

			<xsl:for-each select="dcvalue[@element='description' and @qualifier='abstract']">
			<abstract>
				<xsl:value-of select="./text()" />
			</abstract>
			</xsl:for-each>

			<xsl:for-each select="dcvalue[@element='language' and @qualifier='iso']">
			<language>
				<languageTerm type="code" authority="rfc3066">
				<xsl:value-of select="./text()" />
			</languageTerm>
			</language>
			</xsl:for-each>

			<xsl:for-each select="dcvalue[@element='rights' and @qualifier='none']">
			<accessCondition type="use and reproduction">
				<xsl:value-of select="./text()" />
			</accessCondition>
			</xsl:for-each>			
			<xsl:for-each select="dcvalue[@element='rights' and @qualifier='uri']">
			<accessCondition type="use and reproduction" displayLabel="Creative Commons license">
				<xsl:value-of select="./text()" />
			</accessCondition>
			</xsl:for-each>		
			
			<xsl:for-each select="dcvalue[@element='subject' and @qualifier='lcsh']">
			<subject authority="lcsh">
			  <topic>
				<xsl:value-of select="./text()" />
			  </topic>
			</subject>
			</xsl:for-each>
			<xsl:for-each select="dcvalue[@element='subject' and @qualifier='none']">
			<subject>
			  <topic>
				<xsl:value-of select="./text()" />
			  </topic>
			</subject>
			</xsl:for-each>
			
			<xsl:for-each select="dcvalue[@element='type']">
			<genre>
				<xsl:value-of select="./text()" />
			</genre>
			</xsl:for-each>
			
			<xsl:for-each select="dcvalue[@element='description' and @qualifier='provenance']">
			<note type="ownership">
				<xsl:value-of select="./text()" />
			</note>
			</xsl:for-each>

			<xsl:for-each select="dcvalue[@element='identifier' and @qualifier='citation']">
			<note type="preferred citation">
				<xsl:value-of select="./text()" />
			</note>
			</xsl:for-each>
			
		</mods>
	</xsl:template>
</xsl:stylesheet>

