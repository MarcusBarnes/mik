<?xml version="1.0" encoding="UTF-8"?>
<p:declare-step xmlns:p="http://www.w3.org/ns/xproc" 
    xmlns:c="http://www.w3.org/ns/xproc-step" version="1.0">
    <!-- load multiple files from input directory -->
    <p:directory-list path="xsl/SampleInput"/>
    <p:filter select="//c:file"/>
    <p:for-each name="iterate">
        <p:load>
            <p:with-option name="href" select="concat('xsl/SampleInput/', /*/@name)"/>
        </p:load>
        
        <!-- comment this section out if you want to remove the unmapped fields -->
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/lithographer.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/locationMerge.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/subjectLSU_JJA.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/blankDonor.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/titleNonSort.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/abstractExtent.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        
        <!-- prettify  -->
        <p:xslt>
            <p:input port="stylesheet">
                <p:document href="xsl/OrderedTemplates.xsl"></p:document>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
        </p:xslt>
        
        <!--save multiple files in output directory -->
        <p:store>
            <p:with-option name="href" select="concat('output/', /*/@name)">
                <p:pipe port="current" step="iterate"/>
            </p:with-option>
        </p:store>
    </p:for-each>
</p:declare-step>
