<?php

function transform($mods, $xsl, $out){
    exec("java -jar saxon9he.jar -s:$mods -xsl:$xsl  -o:$out");
}

transform("SampleInput/LSU_JJA-sample.xml", "subjectLSU_JJA.xsl", "testoutput.xml");

