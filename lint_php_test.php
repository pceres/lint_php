	<option <?php if ($themeselect=="themes//") print "selected=\"selected\""; ?>><?php print $pgv_lang["other_theme"]; ?></option>
	</select>
	<option type="text" value="<?php print $NTHEME_DIR; ?>" tabindex="<?php $i++; print $i; ?>" />
<?php

/* commento */ function GetGEDFromZIP($zipfile, $extract=true) {
	if (!empty($gedcom_title)) $gedarray["title"] = $gedcom_title;
	else if (!empty($_POST["gedcom_title"])) $gedarray["title"] = $_POST["gedcom_title"];
	else $gedarray["title"] = str_replace("#GEDCOMFILE#", $GEDFILENAME, $pgv_lang["new_gedcom_title"]);
	if (!$slpos) $slpos = strrpos($zipfile,"\\");
	if ($slpos) $path = substr($zipfile, 0, $slpos+1);
	else $path = $INDEX_DIRECTORY;
        if (isset($_REQUEST['action'])) $action = $_REQUEST['action'];
        if (isset($_REQUEST['action'])) $action = $_REQUEST['action']; elseif ($zipfile==2) $action = 2;
        if (isset($_REQUEST['action'])) {$action = $_REQUEST['action'];} else {$action = 2;}
        if (isset($_REQUEST['action'])) 
	{$action = $_REQUEST['action'];} else {$action = 2;}
        if (empty($extract)) $action = "";

}

function test2($zipfile=null, &$extract=true) {
$a=($zipfile>2)?$extract:2;
;
;;;
$i = 1;
}

function test3(/* comment 1 */ $zipfile, /* comment 2 */  $extract=true) {
$str1 = "\\ \" \' \\ ";
$str2 = ' \" \' \\ ';
$str3 = ' \" \' \\ ';
$str4 = "ciao
ciao2".strlen("ciao
ciao2");
echo "ciao
ciao2
";
      print<<<END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END
// JS-RPC response
if (1) {captcha_rpc = 0;}
END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END;
}

function test4()
{
print "</b></a><br /><br />";
	?>

<?php if ($source!="replace_form") { ?>
<script language="javascript" type="text/javascript">

/*Java Script*/ function getFirstElementsByTagName(node, tag){
	if (!node || !tag || !node.childNodes) {}
	}
</script>
<?php
	}
}

// while cycle outside function definitions
while ($i <= 10) echo $i++;

function test5()
{
while ($i <= 10) {
    echo $i++;  /* the printed value would be
                   $i before the increment
                   (post-increment) */
}
do {
    echo $i++;  /* the printed value would be
                   $i before the increment
                   (post-increment) */
} while ($i <= 10);
while ($i <= 10) echo $i++;
}

echo "Test file is valid PHP code!";

?>