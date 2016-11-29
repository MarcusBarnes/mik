This post-write hook requires the Saxon HE processor. Follow the installation instructions, and place the file saxon9he.jar in your top-level MIK folder. http://www.saxonica.com/html/documentation/about/gettingstarted/gettingstartedjava.html

* Using the config file extras/lsu/configuration_files/HPL.ini as an example specify that you want to use the xslt hook and also the xslts that you wish it to use.

* In your output folder, the subfolder 'original-mods/' will contain your pre-xslt mods files, and the root of the output directory will contain mods that has been transformed by xslt.

![Tree Output](../../lsu/tree.png)

The log file shows the saxon command lines that have been run - useful for debugging (output elided, and spaces included for legibility):

~~~

jason@lappy:~/Documents/mik-latest$ cat output/LSU_JJA_OUTPUT/postwritehook_apply_xslt_success.log 

[2016-04-19 22:37:29] postwritehooks/apply_xslt_with_saxon.php.INFO: Beginning xslt transformations for output/LSU_JJA_OUTPUT/17.xml [] []
[2016-04-19 22:37:29] postwritehooks/apply_xslt_with_saxon.php.INFO: Applying stylesheet extras/lsu/xsl/subjectLSU_JJA.xsl [] []

[2016-04-19 22:37:29] postwritehooks/apply_xslt_with_saxon.php.INFO: Saxon command line: java -jar saxon9he.jar -s:output/LSU_JJA_OUTPUT/17.xml -xsl:extras/lsu/xsl/subjectLSU_JJA.xsl  -o:output/LSU_JJA_OUTPUT/17.xml [] []

[2016-04-19 22:37:48] postwritehooks/apply_xslt_with_saxon.php.INFO: Beginning xslt transformations for output/LSU_JJA_OUTPUT/32.xml [] []
[2016-04-19 22:37:48] postwritehooks/apply_xslt_with_saxon.php.INFO: Applying stylesheet extras/lsu/xsl/subjectLSU_JJA.xsl [] []

[2016-04-19 22:37:48] postwritehooks/apply_xslt_with_saxon.php.INFO: Saxon command line: java -jar saxon9he.jar -s:output/LSU_JJA_OUTPUT/32.xml -xsl:extras/lsu/xsl/subjectLSU_JJA.xsl  -o:output/LSU_JJA_OUTPUT/32.xml [] []

~~~

