<?php
/*

    php_lint_lib: a PHP script that computes McCabe's cyclomatic complexity of a generic PHP source code
    Copyright (C) 2007  Pasquale Ceres
    Version 0.12rc1

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////
function lint($text,$verbosity) {
/*
input:
  $text		: text to analyse
  $verbosity	: [0,1,2] debug level (0 no debug info, 2 max verbosity

es.:
result = lint($text,0);

cyclomatic complexity increase for each token:
if        : number of logical conditions (es. if ($a && $b) {}  determines an increment of two)
elseif    : number of logical conditions
else      : 0

case      : 1
default   : 1

while     : number of logical conditions

for       : 1
foreach   : 1

try       : 1
*/


// configurations
$only_code = 0;    // [0,1] 0 -> also show line numbers
$max_lines = 10000; // max number of lines to show

$parameters = Array();
$parameters['list_begin_end_tokens'] = Array('if','else','elseif','try','catch','while','do','for','foreach');
$parameters['list_mid_tokens'] = Array('else','elseif','catch');


// check input
if (empty($text))
{
	die('Insert some text!');
}


if (!in_array($verbosity,range(0,2)))
{
	stampa('0: no debug info');
	stampa('1: show some debug info');
	stampa('2: show all debug info');
	stampa(' ');
	die('Wrong value for "verbosity"!');
}


// parse input (treat the text as one file)
$list_files = Array($text);


// iterate for each file
$list_result = Array();
for($i_file = 0; $i_file<count($list_files); $i_file++)
{

	$text = $list_files[$i_file];


	// just print file content
	if ($verbosity)
	{
		stampa(sprintf('%3d)',$i_file));
	}


	if ($verbosity)
	{
		stampa(sprintf('Text Reading...'));
	}
	$temp_result = read_phptext($text);
	$list_lines_in = $temp_result[0];
	$list_numlines_in = $temp_result[1];


	if ($verbosity >= 2)
	{
		show_lines($list_lines_in,$list_numlines_in,array_fill(0,count($list_numlines_in),0),$only_code,$max_lines);
	}


	// all text but php code is filtered out:
	// 
	// 	$a=1; /* comment1 */ echo($a) // comment 2
	// becomes
	// 	$a=1;
	// 	echo($a)
	//
	if ($verbosity)
	{
		stampa('Code Filtering...');
	}
	$temp_result = filter_text($list_lines_in,$list_numlines_in);
	$list_lines_code = $temp_result[0];
	$list_numlines_code = $temp_result[1];


	if ($verbosity >= 2)
	{
		show_lines($list_lines_code,$list_numlines_code,array_fill(0,count($list_numlines_code),0),$only_code,$max_lines);
	}


	// every line is split into separate tokens (the separator is ";"):
	// 
	// 	$a=1;echo($a)
	// becomes
	// 	$a=1;
	// 	echo($a)
	//
	if ($verbosity)
	{
		stampa('Code Splitting...');
	}
	$temp_result = parse_mfile($list_lines_code,$list_numlines_code,$parameters['list_begin_end_tokens']);
	$list_lines_split = $temp_result[0];
	$list_numlines_split = $temp_result[1];


	if ($verbosity >= 2)
	{
		show_lines($list_lines_split,$list_numlines_split,array_fill(0,count($list_numlines_split),0),$only_code,$max_lines);
	}


	// each line is reassembled, trying to have one instruction for each line, without blank spaces, and no
	// simple instructions (for example: if (true) disp('true') --> if (true) {disp('true')})
	if ($verbosity)
	{
		stampa('Code Reassembling...');
	}
	$temp_result = reassemble_mfile($list_lines_split,$list_numlines_split,$parameters);
	$list_lines_out = $temp_result[0];
	$list_numlines_out = $temp_result[1];


	if ($verbosity >= 2)
	{
		show_lines($list_lines_out,$list_numlines_out,array_fill(0,count($list_numlines_out)),$only_code,$max_lines);
	}


	if ($verbosity)
	{
		stampa('Code Indenting...');
	}
	$temp_result = indent_mfile($list_lines_out,$list_numlines_out);
	$list_indent_out = $temp_result[0];
	$lista_functions = $temp_result[1];
	$lista_function_names = $temp_result[2];
	$list_lines_out = $temp_result[3];
	$indent_mccount = $temp_result[4];


	$temp_result = check_unused_functions($lista_functions, $lista_function_names);
	$lista_functions = $temp_result[0];
	$is_script  = $temp_result[1];

	
	if ($verbosity >= 2)
	{
		show_lines($list_lines_out, $list_numlines_out, $list_indent_out, $only_code, $max_lines, $indent_mccount);
	}

	
	$res_lint = show_functions($lista_functions, $is_script, $filename, $verbosity);
	
	$result = Array();
	$result['filename']     = $filename;
	$result['lines_in']     = Array('lines' => $list_lines_in, 'numlines' => $list_numlines_in);
	$result['lines_split']  = Array('lines' => $list_lines_split, 'numlines' => $list_numlines_split);
	$result['lines_out']    = Array('lines' => $list_lines_out, 'numlines' => $list_numlines_out, 'list_indent_out' => $list_indent_out, 'indent_mccount' => $indent_mccount);
	$result['lista_functions'] = $lista_functions;


	$result['num_differences'] = $num_differences;
	$result['res_diff'] = $res_diff;
	$result['res_lint'] = $res_lint;


	$list_result[$i_file] = $result;

} // end for $i_file

// end


return $list_result;

} // end function lint()





///////////////////////////////////////////////////////////////////////////////////////////////////////////
function  read_phptext($text) {

$list_lines_in = Array();
$list_numlines_in = Array();

$ks = preg_split("/\r?\n/",$text);


foreach($ks as $line_number => $tline)
{

	$riga_in = $line_number+1;

	array_push($list_lines_in,$tline);
	array_push($list_numlines_in,$riga_in);


} // end while

// end


return Array($list_lines_in, $list_numlines_in);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function show_lines($list_lines,$list_numlines,$list_indent,$only_code,$max_lines,$indent_mccount = Array()) {

stampa('------------------------------------------------------------------------------');


for ($i=0;$i<min(count($list_lines),$max_lines);$i++)
{


	$line = $list_lines[$i];
	$numline = $list_numlines[$i];
	$indent = $list_indent[$i];


	$num_spaces = $indent*4;
	$indent_str = str_repeat('--->',$indent); // indent string (to be added at the left of the line)


	if (count('indent_mccount') > 0)
	{
		$mc_count = $indent_mccount[$i];
		if ($mc_count > 0)
		{
			$ks_mccount = sprintf('(->%1d) - ', $mc_count);
		}
		else
		{
			$ks_mccount = '      - ';
		}
	}
	else
	{
		$ks_mccount = '';
	}

	if ($only_code)
	{
		stampa(sprintf('%s%s%s',$ks_mccount,$indent_str,$line));
	}
	else
	{
		stampa(sprintf('%3d - %s%s%s',$numline,$ks_mccount,$indent_str,$line));
	}

} // end for $i

// end


stampa(' ');


} // end function show_lines



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function filter_text($list_lines_in,$list_numlines_in) {
/*
Drop all text but php code (strings, comments, etc.)
*/

$list_lines_out = Array();
$list_numlines_out = Array();

$enlined_lines = '';
$rem_state = Array('rem_state',0); // out of php code
for($i=0;$i < count($list_lines_in); $i++)
{
	$line = rtrim(strtolower($list_lines_in[$i]));
	$numline = $list_numlines_in[$i];

	// drop all text but PHP code
	$temp_result = pre_split_work($line,$rem_state);
	$line_filtered = $temp_result[0];
	$rem_state = $temp_result[1];

	$new_lines_out = $line_filtered;
	$new_numlines_out = $numline;
	if (strlen($new_lines_out) > 0)
	{
		$list_lines_out[count($list_lines_out)] = $new_lines_out;
		$list_numlines_out[count($list_numlines_out)] = $new_numlines_out;
	}

	// echo "|$line|$line_filtered|{$rem_state['rem_state']}<br>\n";
} // end for $i


return Array($list_lines_out,$list_numlines_out);

} // end function filter_text



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function parse_mfile($list_lines_in,$list_numlines_in,$list_begin_end_tokens) {

$list_lines_out = Array();
$list_numlines_out = Array();

$enlined_lines = '';
$rem_state = Array('rem_state',0); // out of php code
for($i=0;$i < count($list_lines_in); $i++)
{
	$line = $list_lines_in[$i];
	$numline = $list_numlines_in[$i];

	$nextline = $list_lines_in[$i+1];
	$first_char = $nextline[0]; // first char of the next line

	$line_current = $line;

	// condition to enqueue current line, or to stop enqueueing and analyse enqueued text
	$enqueue_to_next_line = (in_array($first_char,Array('?',':'))); // if the first char of next line is ? or :, just enline, as well
	if ($enqueue_to_next_line)
	{
		// enqueue to the next line, and empty current one
		$list_lines_in[$i+1] = $line_current.' '.$list_lines_in[$i+1]; // add current line to the next one
		$list_numlines_in[$i+1] = $numline; // change next line number: it will start with current line number
		$line_current = '';
		$stop_enqueue = false;
	}
	else
	{
		// check the last character of current line
		$last_char = $line_current[strlen($line_current)-1]; // last char of the line
		$stop_enqueue = (in_array($last_char,Array(';','}',')','{'))); // if the last char isn't ; or } or ), just enline
		
		// if line ends by :, and it is a case statement, stop enqueueing
		preg_match("/(case|default)/",$line_current,$z);
		if (($last_char === ':') && (count($z) > 0))
		{
			$stop_enqueue = 1;
		}
	}
	

	$enlined_lines .= $line_current;
	$flg_analyse_line = ($i == count($list_lines_in)-1) || ($stop_enqueue);

	// if the enlines text has to be analysed, do that
	if ($flg_analyse_line)
	{

		// echo "analyzing:|$enlined_lines|<br>";
		if (strlen($enlined_lines) > 0)
		{
			// perform here enqueued line transformations:
			
			// resolve ternary statements into if-then-else
			$enlined_lines = preg_replace('/([^\?]+)\?([^:]+):([^;]+);/','if ($1), {$2;} else {$3;}',$enlined_lines);

			// split enlined text into several atomic lines
			$temp_result = split_line($enlined_lines,$numline,$list_begin_end_tokens);
			$new_lines_out = $temp_result[0];
			$new_numlines_out = $temp_result[1];

			if (count($new_lines_out) > 0)
			{
				$list_lines_out = array_merge($list_lines_out,$new_lines_out);
				$list_numlines_out = array_merge($list_numlines_out,$new_numlines_out);
			}
		}

		// reset the enlined text buffer
		$enlined_lines = '';
	} // end if
} // end for $i

// end


return Array($list_lines_out,$list_numlines_out);

} // end function parse_mfile



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function pre_split_work($line, $rem_state) {
/*
This function takes the input line, and tryes to filter out all text but PHP code.
Strings are substituted by a ' ' default string.
Since comments and string can spread over different lines, a state has to be passed to tell the 
function whether the input line is inside a comment or a string that began in previous lines.
*/

$list_string_tag    = Array('"','\''); // opening and closing multiline string tag
$list_string_tag_id = Array(2  ,3   ); // associated rem_state

// echo "(".$rem_state['rem_state'].")|$line|<br>";

switch ($rem_state['rem_state'])
{
case 0: // out of php: HTML code or inside comments (/*...*/)

	$temp_result = pre_split_work_rem($line, $rem_state); // manage the line in a rem state 
	$line = $temp_result[0];
	$rem_state = $temp_result[1];
	break; // case 0

case 1: // php code

	$temp_result = pre_split_work_phpcode($line, $rem_state, $list_string_tag, $list_string_tag_id);
	$line = $temp_result[0];
	$rem_state = $temp_result[1];
	break; // case 1

// Multiline string delimited by " or ' ( depending on $list_string_tag and $list_string_tag_id)
// It is mandatory that $list_string_tag_id contains the value used by the switch/case's just below
case 2:
case 3:

	$temp_result = pre_split_work_multilinestring($line, $rem_state, $list_string_tag, $list_string_tag_id);
	$line = $temp_result[0];
	$rem_state = $temp_result[1];
	break; // case 2,3

case 4: // heredoc string inside php code
	
	$temp_result = pre_split_work_heredoc($line,$rem_state);
	$line = $temp_result[0];
	$rem_state = $temp_result[1];
	break; // case 4

default:
	die(sprintf('Unmanaged rem_state (%d)!',$rem_state['rem_state']));
	break;

} // end switch($rem_state['rem_state'])

// end


$line = drop_strings($line);


// substitute commas with spaces inside input parameter text of functions
if (preg_match('/\s*function/',$line)) // if the line starts with "function" token...
{
	$lista_pat = Array('function\s*\[[^\]]*\]','function\s*[^\(]*\([^\)]*\)');
	
	for ($i_pat = 0;$i_pat < count($lista_pat); $i_pat++)
	{
		$pat = "/".$lista_pat[$i_pat]."/i";
		preg_match($pat,$line,$z);
		if (!empty($z))
		{
			// delete semicolon deriving from enclosed remarcs inside function parameters
			// (es. function foo($bar1 /*note */,$bar2) --> foo($bar1;, $bar2) --> foo($bar1 $bar2)  )
			$z[0]=str_replace(';','',$z[0]);
			
			// substitute commas with spaces (es. function foo($bar1,$bar2) --> foo($bar1 $bar2)   )
			$z=str_replace(',',' ',$z[0]);
			$line = preg_replace($pat,$z,$line);
		}
	}
}	


// if a simple instruction (not inclosed in brackets {}) follows an intermediate token (else, catch, etc.), then 
// insert a semicolon (ex: "else echo('1')" -->  "else; echo('1')"
$line = preg_replace('/(else|catch)[\s]+/i',"\\1;",$line);


$line = trim($line);
$line_elaborata = $line;

return Array($line_elaborata,$rem_state);

} // end function pre_split_work



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function pre_split_work_heredoc(&$line,&$rem_state) {
/*
This function manages the $line when in a heredoc string state ($rem_state['rem_state'] = 4)
*/

// search for heredoc string closing tag
$string_tag = '^'.$rem_state['heredoc_close_tag'].';?$';
if ( preg_match("/$string_tag/",$line) )
{
	$rem_state['rem_state'] = 1; // back to PHP code
	unset($rem_state['heredoc_close_tag']);
}

$line = '';

return Array($line,$rem_state);

} // end function pre_split_work_heredoc



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function pre_split_work_multilinestring(&$line,&$rem_state,$list_string_tag,$list_string_tag_id) {
/*
This function manages the $line when in a multiline string state ($rem_state['rem_state'] = 2 or 3)
*/

// determine the string delimiter that originated the multiline string
$string_tag = $list_string_tag[array_search($rem_state['rem_state'], $list_string_tag_id)];

// drop escapes (hopefully inside strings!)
$line2 = drop_escapes($line);

// search for string closing tag
preg_match_all("/$string_tag/",$line2,$z);
//if (count($z[0]) % 2 == 1 ) // an odd number means the multiline string closes
if ( count($z[0]) > 0 ) // if you find at least one tag, manage it
{
	// pointer next to first string_tag
	$ind = strfind($line2,$string_tag)+1;
	
	$rem_state['rem_state'] = 1; // back to PHP code

	$temp_result = pre_split_work(substr($line2,$ind), $rem_state);
	$line3 = $temp_result[0];
	$rem_state = $temp_result[1];
	
	$line = $line3;
}
else
{
	$line = '';
}

return Array($line,$rem_state);

} // end function pre_split_work_multilinestring


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function pre_split_work_phpcode(&$line,&$rem_state,$list_string_tag,$list_string_tag_id) {
/*
This function manages the $line when in a php state ($rem_state['rem_state'] = 1)
*/
	
// drop escapes (hopefully inside strings!)
$line = drop_escapes($line);

// in PHP code, you can drop strings
$line = drop_strings($line);

// search for rem closing tags
$tag = '?>';
$rem_state_restart_tag = '<?php'; // tag to search in following text to exit the out-of-php-code state (0)
$ind = strfind($line,$tag);

if ($ind === False)
{
	$tag = '/*';
	$rem_state_restart_tag = '*/'; // tag to search in following text to exit the out-of-php-code state (0)
	$ind = strfind($line,$tag);
}

// verify if a potential rem closing tag is inside a string
if ( ($ind > 0) && (strlen($line) > 0) )
{
	$line = drop_strings(substr($line,0,$ind)).substr($line,$ind);
	$ind = strfind($line,$tag);
	
	preg_match_all("/'/",substr($line,0,$ind),$z);
	if ( count($z[0]) % 2 == 1 )
	{
		$ind = False; // if so, don't consider it
	}
}

// if a rem tag ("<?php" or "/*") was found, change the state
if ($ind !== False)
{
	$line2 = substr($line,0,$ind);
	$rem_state['rem_state'] = 0;
	$rem_state['rem_state_restart_tag'] = $rem_state_restart_tag; // save the restart tag: only its occurrence will restart php code
	
	$temp_result = pre_split_work(substr($line,$ind+strlen($tag)), $rem_state);
	$line3 = $temp_result[0];
	$rem_state = $temp_result[1];
// 		$line = $line2.';'.$line3;
	$line = $line2.' '.$line3; // join code before and after a /*...*/ comment by a blank space
}


// search for multiline unclosed string
$line2 = drop_strings($line);
for ($i_tag = 0;$i_tag < count($list_string_tag);$i_tag++)
{
	$string_tag = $list_string_tag[$i_tag];
	$string_tag_id = $list_string_tag_id[$i_tag];
	preg_match_all("/$string_tag/",$line2,$z);
	if ( count($z[0]) % 2 == 1 )
	{
		$line = $line2." ".$string_tag;
		$rem_state['rem_state'] = $string_tag_id;
		break;
	}
}

// search for heredoc string opening tag
$string_tag = '<<<([a-zA-Z_][a-zA-Z0-9_]+)$';
preg_match_all("/$string_tag/",$line,$z);

if ( count($z[1][0]) > 0 )
{
	$heredoc_close_tag = $z[1][0];
	preg_match("/$string_tag/",$line,$z,PREG_OFFSET_CAPTURE);
	$pos = $z[0][1];
	
	$line2 = drop_strings(substr($line,0,$pos));
	$line = $line2.' " ";';
	$rem_state['rem_state'] = max($list_string_tag_id)+1;
	$rem_state['heredoc_close_tag'] = $heredoc_close_tag;
}

return Array($line,$rem_state);

} // end function pre_split_work_phpcode



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function pre_split_work_rem(&$line,&$rem_state) {
/*
This function manages the $line when in a rem state ($rem_state['rem_state'] = 0)
*/

// retrieve the rem opening tag (previously saved when the rem state was set, otherwise "<?php")
if (empty($rem_state['rem_state_restart_tag']))
{
	$tag = '<?php';
}
else
{
	$tag = $rem_state['rem_state_restart_tag'];
}

// search for rem opening tags (previously saved when the rem state was set)
$ind = strfind($line,$tag);
if ($ind===False)
{
	$line = '';
}
else
{
	$line = substr($line,$ind+strlen($tag));
	$rem_state['rem_state'] = 1;
	
	$temp_result = pre_split_work($line,$rem_state);
	$line = $temp_result[0];
	$rem_state = $temp_result[1];
}

return Array($line,$rem_state);

} // end function pre_split_work_rem



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function drop_comments($line) {

$ind_rem = strpos($line,'#');
if ($ind_rem !== False)
{
    $line = trim(substr($line,0,$ind_rem));  // drop comments
}

$ind_rem = strpos($line,'//');
if ($ind_rem !== False)
{
    $line = trim(substr($line,0,$ind_rem));  // drop comments
}


return $line;

} // function drop_comments($line)



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function drop_escapes($line) {
/*
This function substitutes or drops special escape sequences (typical inside strings),
such as "\'", "\\"
*/

$list_tag_replace = Array(
    Array("/\\\\\\\\/","_"),	// substitute \\ by _ (must be first, to prevent "\\" to be transformed into "\_ )
    Array("/\\\'/","_"),	// substitute \' by _
    Array("/\\\\\"/","_")	// substitute \" by _
    );


for ($i_tag = 0; $i_tag < count($list_tag_replace); $i_tag++)
{
	$tag_old = $list_tag_replace[$i_tag][0];
	$tag_new = $list_tag_replace[$i_tag][1];
	
	$line2='';
	while (strlen($line)!=strlen($line2))
	{
		$line2 = $line;
		$line = preg_replace($tag_old,$tag_new,$line2,1);
	}
// 	echo "$i_tag($tag_old|$tag_new)$line|$line2|<br>\n";
}

// $line = preg_replace("/''/i","'_'",$line);      // substitute repeated single quotes with '_'
// $line = preg_replace('/""/i','"_"',$line);      // substitute repeated double quotes with "_"

return $line;

} // end function drop_escapes($line)



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function drop_strings($line) {


$line = drop_escapes($line);


// sostituisci stringhe con "_"
$string_tag = '^[^\'"]*([\'"])';
preg_match("/$string_tag/",$line,$z);
$ancora = 1;
while ( (count($z) > 0) && ($ancora) )
{
	$string_delimiter = $z[1];
	$string_tag2 = $string_delimiter.'([^'.$string_delimiter.']*)'.$string_delimiter;
	$line2 = preg_replace("/$string_tag2/",'@_@',$line,1);
	
	if ( $line2 === $line )
	{
		$ancora = 0;
	}
	else
	{
		$line = $line2;
		preg_match("/$string_tag/",$line,$z);
	}
}
$line = preg_replace('/@_@/','"_"',$line);


$line = drop_comments($line);


return $line;

} // end function drop_strings($line)


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function split_line($line,$numline,$list_begin_end_tokens) {

$new_lines_out       = Array();
$new_numlines_out    = Array();

$split_tags = Array(';','{','}'); // tags that cause the line to be split
$drop_tags = Array(';');          // tags that, alone in a line, are dropped

$line = trim($line);
if (!empty($line))
{

	$pieces = explode_lines(Array($line),$split_tags,$list_begin_end_tokens);

	for ($i_pieces = 0; $i_pieces < count($pieces); $i_pieces++)
	{
		$piece = trim($pieces[$i_pieces]);
		
		// add the slice, if it isn't empty, and it is not a tag to be dropped
		if  (!empty($piece) && !in_array($piece,$drop_tags))
		{
			array_push($new_lines_out,$piece);
			array_push($new_numlines_out,$numline);
		}
	}
} // end if

return Array($new_lines_out, $new_numlines_out);

} // end function split_line



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function explode_lines($pieces,$list_sep,$list_begin_end_tokens) {

for ($i_sep = 0; $i_sep < count($list_sep); $i_sep++)
{
	$sep = $list_sep[$i_sep];


	$temp_pieces = Array();
	for ($i_pieces = 0; $i_pieces < count($pieces); $i_pieces++)
	{
		$piece = $pieces[$i_pieces];
		
		$temp = fcn_explode($piece,$sep,1);
		
		$temp_pieces = array_merge($temp_pieces,$temp);
	}


	$pieces = $temp_pieces;


} // end for


// check if a single line "if" is in the line, and split it (es.: "if (!$slpos) $slpos = 18;" is split into
// "if (!$slpos)" and "$slpos = 18;"
if (count($list_begin_end_tokens))
{
	// prepare the regexp format
	$ks_format = '';
	foreach ($list_begin_end_tokens as $token)
	{
	$ks_format .= "$token|";
	}
	$ks_format = substr($ks_format,0,-1);
	$ks_format = "/^[\s;]($ks_format)\s+\((.*)\)\s+([^\{;]+);/";

	// check each piece for the format
	$temp_pieces = Array();
	for ($i_piece = 0; $i_piece < count($pieces); $i_piece++)
	{
		$line_temp    = $pieces[$i_piece];
		
		preg_match($ks_format," $line_temp",$z); // add a leading space, to match pieces that begin with the token
		
		if (!empty($z))
		{
			$ks_token_start = $z[1]; // "if"
			$ks_condition   = $z[2]; // "!$slpos"
			$ks_action1     = $z[3]; // "$slpos = 18"
			
			$line1 = "$ks_token_start ($ks_condition)";
			$line2 = "$ks_action1;";
			
			array_push($temp_pieces,$line1);
			array_push($temp_pieces,$line2);
		}
		else
		{
			array_push($temp_pieces,$line_temp);
		}
	} // end for
} // end if

$pieces = $temp_pieces;

return $pieces;

} // end function explode_lines



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function reassemble_mfile($list_lines_in,$list_numlines_in,$parameters) {

$list_lines_out = Array();
$list_numlines_out = Array();


$list_open  = Array('(');
$list_close = Array(')');


$list_begin_end_tokens = $parameters['list_begin_end_tokens'];
$list_mid_tokens       = $parameters['list_mid_tokens'];


$parenthesys_flag = 0;
$newline = 1;
$num_open = 0;
$num_close = 0;
$line_step = 100;

// prepare the regexp format
$ks_format = '';
foreach ($list_begin_end_tokens as $token)
{
$ks_format .= "$token|";
}
$ks_format = substr($ks_format,0,-1);


for ($i=0;$i<count($list_lines_in);$i++) {

	$line    = $list_lines_in[$i];
	$numline = $list_numlines_in[$i];

	if (strpos($line,"...")!==false)
	{
		$line = str_replace('...','',$line);
	}


	// if an elseif (or similar) follows, don't go to new line
	if ($newline)
	{
		for ($i_mid = 0;$i_mid < count($list_mid_tokens);$i_mid++)
		{
			$mid_token = $list_mid_tokens[$i_mid];
			
			if (strpos($line,$mid_token)!==false)
			{
				$newline = 0;
			}
		}
	}


	// if an open brace parenthesis { is at the beginning of the line, then move it at the end of the previous line
	if ( (strpos($line,'{') !== false) && (strpos($line,'{') === 0) )
	{
		$newline = 0;
	}


	// if previous token manages complex instructions ({...}, parenthesys_flag=1), and a simple instruction follows,
	// surrond it by braces (making it a complex instruction)
	if ($newline)
	{
		// a command should not be embraced if an opening parenthesis is found
		$embrace_line = (strcmp($line,'{') !== 0); 



		if ( $parenthesys_flag && $embrace_line )
		{
			// no embracing if next command is a complex one, as well: better not embracing
			// es.: if ($a==1) $b=1; else if ($a==2) $b=2; else $b=3;
			preg_match("/^\s*($ks_format)/",$line,$z);
			if (count($z) > 0)
			{
				// Add a virtual empty atomic instruction, just before the complex instruction that starts here
				// the indentation will be at the wrong level for the latter, but following instructions will be indented
				// correctly
				$add_line = Array('{/*multiline atomic instruction without curly braces: dropped*/}',$line);
			}
			else
			{
				// Add an open curly brace to the previous line ...
				$list_lines_out[count($list_lines_out)-1] = $list_lines_out[count($list_lines_out)-1].',{';
				
				// ... and a new line with a close curly brace after the current line
				$add_line = Array($line,'}');
			}
		}
		else
		{
			$add_line = Array($line);
		}
		$add_numline = array_fill(0,count($add_line),$numline);
		$list_lines_out = array_merge($list_lines_out,$add_line);
		$list_numlines_out = array_merge($list_numlines_out,$add_numline);
	}
	else
	{
		$list_lines_out[count($list_lines_out)-1] = $list_lines_out[count($list_lines_out)-1].','.$line;
	}


	// count open and close brackets
	for ($i_open = 0;$i_open<count($list_open);$i_open++)
	{
		$num_open  = $num_open+count(explode($list_open[$i_open],$line))-1;
		$num_close = $num_close+count(explode($list_close[$i_open],$line))-1;
	}


	// determine whether line terminates correctly (and you can go to a new line), or it is incomplete (and it continues
	// in the following line)
	$is_complete = (empty($line) || (!in_array(substr($line,-1,1),Array('|','&'))) );
	if ( ($num_open == $num_close) && $is_complete )
	{
		$newline = 1;
	}
	else
	{
		$newline = 0;
	}


	// verify that a { is present in following line, or treat the following instruction as a simple instruction
	$parenthesys_flag = 0;
	for ($i_tok = 0; $i_tok < count($list_begin_end_tokens); $i_tok++)
	{
		$tok = $list_begin_end_tokens[$i_tok];
		
		////if ( (strpos($line,$tok) === 0) && (ereg($tok.'[^{]*{',$line)===false) )
		if ( (strpos($line,$tok) === 0) && (preg_match('/' . $tok . '[^{]*{/',$line)==0) )
		{
			$parenthesys_flag = 1;
		}
	}


	// a 'while' after 'do' isn't followed by a complex instruction
	////if ($parenthesys_flag && (ereg('^while.*;$',$line)!==false) )
	if ($parenthesys_flag && (preg_match('/^while.*;$/',$line)>0) )
	{
		$parenthesys_flag = 0;
	}

} // end for

// end


return Array($list_lines_out,$list_numlines_out);

} // end function reassemble_mfile


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function fcn_explode($line,$sep,$flag_keep_sep) {

$ind = fcn_strpos($line,$sep); // all positions where separator is found


if ($ind === False)
{

	$piece = normalizza_riga($line);
	$pieces = Array($piece);

}
else
{

	$ind = array_merge($ind,Array(strlen($line)));
	$lsep = strlen($sep);


	if ($flag_keep_sep)
	{
		$first_piece = substr($line,0,$ind[0]+strlen($sep));
	}
	else
	{
		$first_piece = substr($line,0,$ind[0]);
	}
	$pieces = Array(normalizza_riga($first_piece));


	for ($i=1;$i<count($ind);$i++)
	{


		if ($flag_keep_sep)
		{
			$piece = substr($line,$ind[$i-1]+$lsep,$ind[$i]-$ind[$i-1]);
		}
		else
		{
			$piece = substr($line,$ind[$i-1]+$lsep,$ind[$i]-$ind[$i-1]-$lsep);
		}



		$piece = normalizza_riga($piece);
		
		if (!empty($piece))
		{
			array_push($pieces,$piece);
		}
		
	
	} // end for

//     end


} // end if

// end


return $pieces;

} // end function fcn_explode




///////////////////////////////////////////////////////////////////////////////////////////////////////////
function indent_mfile($list_lines_in,$list_numlines_in) {

$list_indent = Array();


$list_inc0    = Array('for'     ,'foreach'     ,'if'                        ,'try'            ,'switch'                        ,'while'   ,'do'        );
$list_mid0    = Array(Array('') ,Array('')     ,Array('},elseif' ,'},else') ,Array('},catch') ,Array('},case'   ,'},default')  ,Array('') ,Array('')   );
$list_dec0    = Array('}'       ,'}'           ,'}'                         ,'}'              ,'}'                             ,'}'       ,'}'         );


// add a space to tokens to be searched for
$token_append_string = ' ';
for ($i_inc = 0; $i_inc < count($list_inc0); $i_inc++)
{
	$list_inc[$i_inc]  = $list_inc0[$i_inc].$token_append_string;
	
	$list_mid_ = $list_mid0[$i_inc];
	$list_mid[$i_inc] = Array();
	for ($i_mid = 0; $i_mid < count($list_mid_); $i_mid++)
	{
		$list_mid[$i_inc][$i_mid]  = $list_mid_[$i_mid].$token_append_string;
	}
	
	$list_dec[$i_inc]  = $list_dec0[$i_inc].$token_append_string;
}



$indent_tokens['list_inc0'] = $list_inc0;
$indent_tokens['list_mid0'] = $list_mid0;
$indent_tokens['list_dec0'] = $list_dec0;
$indent_tokens['list_inc'] = $list_inc;
$indent_tokens['list_mid'] = $list_mid;
$indent_tokens['list_dec'] = $list_dec;


$indent      = 0;
$indent_next = 0;
$token       = Array();
$list_token  = Array($token);
$list_tokens = Array();
$function_info = get_function_info('');
$lista_functions = Array();
$lista_function_names = Array();
$stati       = Array('line_modified' => '');


for ($i = 0; $i < count($list_lines_in); $i++)
{


	$line    = $list_lines_in[$i].' ';
	$numline = $list_numlines_in[$i];

	$temp_result = indent_line($line, $numline, $indent, $indent_tokens, $token, $list_tokens, $function_info, $list_token, $lista_functions, $lista_function_names, $stati);
	$token = $temp_result[0];
	$token_next = $temp_result[1];
	$indent = $temp_result[2];
	$indent_next = $temp_result[3];
	$list_token = $temp_result[4];
	$list_tokens = $temp_result[5];
	$function_info = $temp_result[6];
	$lista_functions = $temp_result[7];
	$lista_function_names = $temp_result[8];
	$stati = $temp_result[9];
	$mc_inc = $temp_result[10];

// echo "$numline-->$indent<br>";	// !!!
// var_dump($function_info['function']);echo "<br>";


	// if the line has to be changed
	if (!empty($stati['line_modified']))
	{
		$list_lines_in[$i] = $stati['line_modified'];
		$stati['line_modified'] = '';
	}


	$list_mc_count_in[$i] = $mc_inc;


	$list_indent[$i] = $indent;
	$indent = $indent+$indent_next;


} // end for

// end


$function_info = function_checks($function_info,$list_tokens);
array_push($lista_functions, $function_info);


return Array($list_indent, $lista_functions, $lista_function_names, $list_lines_in, $list_mc_count_in);

} // end function indent_mfile



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function indent_line($line, $numline, $indent, $indent_tokens, $token, $list_tokens, $function_info, $list_token, $lista_functions, $lista_function_names, $stati) {

// insert a space before open braces
////$line = ereg_replace('([^[:space:]]){',"\\1 {",$line);
$line = preg_replace('/([^[:space:]]){/',"\\1 {",$line);

$mc_inc = 0; // by default each line doesn't increment the McCabe cyclomatic complexity counter

$list_inc0 = $indent_tokens['list_inc0'];
$list_mid0 = $indent_tokens['list_mid0'];
$list_dec0 = $indent_tokens['list_dec0'];
$list_inc  = $indent_tokens['list_inc'];
$list_mid  = $indent_tokens['list_mid'];
$list_dec  = $indent_tokens['list_dec'];


$token_next  = Array();
$indent_next = 0;

// if indent counter is zero (top level), and I find a }, it means that the current function closes here
if ( ($indent == 0) && ($line === '} ') )
{
	$close_function = true;
}
else
{
	$close_function = false;
}

// a 'do' structure was just closed --> request the substitution of 'while' with 'while_do'
if (array_key_exists('subst_line',$stati) && (!empty($stati['subst_line'])))
{

	////$line = ereg_replace($stati['subst_line'][0],$stati['subst_line'][1],$line);
	$line = preg_replace('/' . $stati['subst_line'][0] . '/',$stati['subst_line'][1],$line);
	$stati['line_modified'] = $line;
	$stati['subst_line'] = Array();

}
else
{

	// a new complex instruction starts?
	for ($i_inc = 0; $i_inc < count($list_inc0); $i_inc++)
	{
		////if (ereg('^'.$list_inc0[$i_inc].'[^[:alnum:]]?',$line))
		if (preg_match('/^'.$list_inc0[$i_inc].'[^[:alnum:]]?/',$line))
		{
			$token_next['inc'] = $list_inc0[$i_inc];
			$token_next['mid'] = $list_mid0[$i_inc];
			$token_next['dec'] = $list_dec0[$i_inc];
			$indent_next = 1;
			break;
		} // end if
	} // end for


	if (array_key_exists('dec',$token))
	{
		// if you're inside a complex instruction, delimited by braces, for which closing tags exist
		// (ex. '}', and other intermediate (ex. 'else', 'elseif')...
		
// var_dump($token);echo "<br>"; // !!!
// stampa("1($indent,$indent_next)"); // !!!

		// search intermediate token (ex. else)
		$temp_result = manage_mid_token($line,$token,$indent,$indent_next);
		$indent = $temp_result[0];
		$indent_next = $temp_result[1];

// stampa("2($indent,$indent_next)"); // !!!

		// search closing token (ex. })
		$temp_result  = manage_dec_token($line, $numline, $token, $list_token, $indent, $indent_next, $function_info, $stati);
		$indent = $temp_result[0];
		$indent_next = $temp_result[1];
		$token = $temp_result[2];
		$list_token = $temp_result[3];
		$function_info = $temp_result[4];
		$stati = $temp_result[5];

// var_dump($token);echo "<br>"; // !!!
// stampa("3($indent,$indent_next)"); // !!!
	} // end if

} // end if

// end


// current function finishes, a new starts (included the root function, for scripts not inside function tags)
//// if ( (ereg('^(private |public |protected )*function ',$line)) || ($close_function) )
if ( (preg_match('/^(private |public |protected )*function /',$line)) || ($close_function) )
{

	// first verify the tokens found in previous function...
	$function_info = function_checks($function_info,$list_tokens);
	array_push($lista_functions, $function_info);


	// ...then start new function
	$function_info = get_function_info($line);
	$list_tokens = Array();
	array_push($lista_function_names, $function_info['function']);


}
else
{

	// verify whether any of the tokens is an input or output parameter
	preg_match_all('/[[:alnum:]_$]+/',$line,$temp_ereg_result);
	if (empty($line_tokens))
	{
		$line_tokens = $temp_ereg_result[0];
	}
	else
	{
		$line_tokens = array_merge($line_tokens, $temp_ereg_result[0]);
	}


	$list_tokens = array_unique(array_merge($list_tokens, $line_tokens));


	$mc_inc = lint_check($line,$line_tokens);
	$function_info['mc_count'] = $function_info['mc_count']+$mc_inc;

} // end if

// end


if (array_key_exists('inc',$token_next))
{
	$token = $token_next;
	array_push($list_token, $token_next);
}


return Array($token, $token_next, $indent, $indent_next, $list_token, $list_tokens, $function_info, $lista_functions, $lista_function_names, $stati, $mc_inc);

} // end function indent_line



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function lint_check($line, $line_tokens) {

$list_tok    = Array('if' ,'elseif','while_do','while','try'  ,'case' ,'default','for'  ,'foreach'    );
$num_tok     = Array(1    ,1       ,1         ,1      ,0      ,0      ,0        ,0      ,0            );


$mc_inc = 0;
for ($i_token = 0; $i_token < count($line_tokens); $i_token++)
{


	$token = $line_tokens[$i_token];


	$ind = array_search($token,$list_tok);
	if ($ind !== false)
	{
		$mc_inc = 1;


		if ($i_token >= count($num_tok))
		{
			stampa($line);
			die('error!');
		}
		if ($num_tok[$i_token])
		{
			preg_match_all('/&+/',$line,$and1);
			preg_match_all('/[^[:alnum:]_]and[^[:alnum:]_]?/',$line,$and2);
			$ands = count($and1[0])+count($and2[0]);
			
			preg_match_all('/\|+/',$line,$or1);
			preg_match_all('/[^[:alnum:]_]or[^[:alnum:]_]?/',$line,$or2);
			$ors = count($or1[0])+count($or2[0]);
			
			$mc_inc = $mc_inc+$ands+$ors;
		}
		break;


	} // end if

//     end


} // end for

// end


return $mc_inc;

} // end function lint_check



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function manage_mid_token($line,$token,$indent,$indent_next) {


$ancora=1;
$i_mid=0;
$ind_mid = null;
while ($ancora)
{

	$needle = $token['mid'][$i_mid];

	////if (ereg($needle,$line))
	if (!empty($needle) && preg_match('/' . addcslashes($needle, '/') . '/',$line))
	{
		$ancora=0;
		$ind_mid = $i_mid;
// echo "1|$needle|$line|<br>"; // !!!
// var_dump($ind_mid === null); // !!!
	}
	else
	{
		$i_mid = $i_mid+1;
		$ancora = $i_mid < count($token['mid']);
// echo "2<br>"; // !!!
	}


} // end while

// end



// if you find a mid token (ex. else, elseif, ecc.) decrement current line indentation (but increment again at next line)
if ($ind_mid !== null)
{
    $indent = $indent-1;
    $indent_next = 1;
} // end if


return Array($indent,$indent_next);

} // end function manage_mid_token


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function manage_dec_token($line, $numline, $token, $list_token, $indent, $indent_next, $function_info, $stati) {

$stati['subst_line'] = Array(); // by default don't require any line substitution

$ind_dec = fcn_strpos($line,$token['dec'].' ');
if ($ind_dec!==false)
{
	// if current structure is a do, request the substitution of the closing 'while' token with 'while_do'
	if (strcmp($token['inc'],'do') === 0)
	{
		$stati['subst_line'] = Array('^while([^[:alnum:]]?)',"while_do\\1");
	} // end if


	$indent = $indent-1;
	$indent_next = 0;
	$list_token = array_splice($list_token,0,-1);


	if (empty($list_token))
	{
		$msg = sprintf('Errore nella linea %d',$numline);
		if (!empty($function_info))
		{
			$function_info['final_end'] = 1;
			stampa($msg);
		}
		else
		{
			die($msg);
		}
	}
	else
	{
		$token = $list_token[count($list_token)-1];
	}

} // end if

// end


return Array($indent, $indent_next, $token, $list_token, $function_info, $stati);

} // end function manage_dec_token



///////////////////////////////////////////////////////////////////////////////////////////////////////////
function function_checks($function_info,$list_tokens) {


$function_info['list_tokens'] = $list_tokens;
$list_tokens = array_merge($list_tokens,Array('varargin','varargout'));


// unused input arguments
$lista_param = $function_info['list_args_in'];
$tipo_param = 'Input';

$unused_var = Array();
foreach ($lista_param as $id => $param)
{
	if (!in_array($param,$list_tokens))
	{
		array_push($unused_var,$param);
	}
}

$function_info['unused_inputs'] = $unused_var;


// unused output arguments
$lista_param = $function_info['list_args_out'];
$tipo_param = 'Output';

$unused_var = Array();
foreach ($lista_param as $id => $param)
{
	if (!in_array($param,$list_tokens))
	{
		array_push($unused_var,$param);
	}
}

$function_info['unused_outputs'] = $unused_var;


return $function_info;

} // end function_checks


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function get_function_info($line) {

if (empty($line) || ($line == '} '))
{
    $function_info = Array('function' => '');
    $function_info['args_in'] = '';
    $function_info['args_out'] = '';
    $function_info['list_args_in'] = Array();
    $function_info['list_args_out'] = Array();
    $function_info['mc_count'] = 1;

    return $function_info;
}


$list = Array('[[:space:]]*function[[:space:]]+&?[[:space:]]*([^\(]*)[[:space:]]*\([[:space:]]*([^\)]*)');


$ancora = 1;
$i = 0;
while ($ancora)
{
	$pat = $list[$i];
	$function_info = Array();
	////$temp = ereg($pat,$line,&$temp_function_info);
	$temp = preg_match('/' . addcslashes($pat, '/') . '/',$line,&$temp_function_info);
	$function_info['function'] = $temp_function_info[1];
	$function_info['args_in'] = $temp_function_info[2];

	$i = $i+1;
	$ancora = ( empty($function_info) && ($i < count($list)) );
}


if (!array_key_exists('args_out',$function_info))
{
    $function_info['args_out'] = '';
}
if (!array_key_exists('function',$function_info))
{
    $function_info['function'] = '';
}
if (!array_key_exists('args_in',$function_info))
{
    $function_info['args_in'] = '';
}


if (empty($function_info['function']))
{
    stampa('Problema!');
    die($line);
}


$flag_keep_sep = 0;


if (!empty($function_info['args_out']))
{
	$function_info['list_args_out'] = explode_lines(Array($function_info['args_out']),Array(',',' '),Array());
}
else
{
	$function_info['list_args_out'] = Array();
}


if (!empty($function_info['args_in']))
{
	// drop "&" for referenced arguments
	////$function_info['args_in'] = ereg_replace('&','',$function_info['args_in']);
	$function_info['args_in'] = preg_replace('/&/','',$function_info['args_in']);

	// drop default values ("... $a=NULL ..." --> "... $a ..." )	
	////$function_info['args_in'] = ereg_replace('=[[:space:]]*[^[:space:]]+[[:space:]]*', ' ',$function_info['args_in']);
	$function_info['args_in'] = preg_replace('/=[[:space:]]*[^[:space:]]+[[:space:]]*/', ' ',$function_info['args_in']);

	// split input arguments
	$function_info['list_args_in'] = explode_lines(Array($function_info['args_in']),Array(',',' '),Array());
}
else
{
    $function_info['list_args_in'] = Array();
}


$function_info['mc_count'] = 1;


return $function_info;

} // end function get_function_info


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function check_unused_functions($lista_functions, $lista_function_names) {


if (count($lista_functions) == 1)
{
	$is_script = 1;
}
else
{
	$is_script = 0;
}


$unused_fcns = $lista_function_names;


for ($i_fcn = 0; $i_fcn < count($lista_function_names); $i_fcn++)
{
	$function_info = $lista_functions[$i_fcn];
	$fcn_tokens = $function_info['list_tokens'];
	
	$unused_fcns = array_diff($unused_fcns, $fcn_tokens);
}


for ($i_fcn = 0; $i_fcn < count($lista_functions); $i_fcn++)
{
	$function_info = $lista_functions[$i_fcn];
	$fcn_name = $function_info['function'];
	if ( (in_array($fcn_name, $unused_fcns)) && (!empty($fcn_name)) )
	{
		$unused_flag = 1;
	}
	else
	{
		$unused_flag = 0;
	}

// echo("$fcn_name : $unused_flag<br>"); // !!!


	$function_info['unused_flag'] = $unused_flag;
	$lista_functions[$i_fcn] = $function_info;
}


return Array($lista_functions, $is_script);

} // end function check_unused_functions


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function normalizza_riga($riga) {

// elimina commenti
$ind = strpos($riga,'#');
if ($ind !== False)
{
    $riga = substr($riga,0,$ind);
}


// drop blank spaces ad the beginning and the end of the line
$riga = trim($riga);


return $riga;

} // end function normalizza_riga


///////////////////////////////////////////////////////////////////////////////////////////////////////
function show_functions($lista_functions, $is_script, $filename, $verbosity) {

$result = Array();
$ks_space = str_repeat('&nbsp;',8);


if ($verbosity)
{
    stampa(' ');
}


for ($i_fcn = 0; $i_fcn < count($lista_functions); $i_fcn++)
{

	$function_info = $lista_functions[$i_fcn];


	$fcn_name = $function_info['function'];
	$fcn_mc_count = $function_info['mc_count'];
	$fcn_unused_inputs = $function_info['unused_inputs'];
	$fcn_unused_outputs = $function_info['unused_outputs'];
	$fcn_unused_flag = $function_info['unused_flag'];

// echo "$i_fcn<br><br>";	// !!!
// var_dump($function_info);
// echo "<br><br>";

	// check unused output
	if (!empty($fcn_unused_outputs))
	{
		for ($i = 0; $i < count($fcn_unused_outputs); $i++)
		{
			$result = print_msg($result,sprintf("%sFunction return value '%s' appears to never be set.",$ks_space,$fcn_unused_outputs[$i]),$verbosity);
		}
	}


	// McCabe cyclomatic complexity
	if (empty($fcn_name))
	{
		$fcn_msg = '';
		if ($fcn_mc_count > 1)
		{
			$show_line = true;
		}
		else
		{
			$show_line = false;
		}
	}
	else
	{
		$fcn_msg = sprintf("of '%s' ",$fcn_name);
		$show_line = true;
	}
	
	if ($show_line)
	{
        	$result = print_msg($result,sprintf('The McCabe complexity %sis %d.', $fcn_msg, $fcn_mc_count), $verbosity);
	}


	// check declared but unused functions
	$result = manage_unused_function($fcn_name, $fcn_unused_flag, $is_script, $i_fcn, $filename, $result, $verbosity);


	// check unused input
	if (!empty($fcn_unused_inputs))
	{
		for ($i = 0; $i < count($fcn_unused_inputs); $i++)
		{
			$result = print_msg($result,sprintf("%sInput variable '%s' appears never to be used.",$ks_space,$fcn_unused_inputs[$i]),$verbosity);
		}
	}


} // end for

// end


return $result;

} // end function show_functions


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function manage_unused_function($fcn_name, $fcn_unused_flag, $is_script, $i_fcn, $filename, $result, $verbosity) {

$ks_space = str_repeat('&nbsp;',8);


// mfile with first function different from file name
if ((!$is_script) && ($i_fcn == 1))
{

	////$ind = ereg('[\\\/]',$filename);
	$ind = preg_match('/[\\\/]/',$filename);
	if (!empty($ind))
	{
		$filename = substr($filename,$ind);
	}

}
elseif ($fcn_unused_flag)
{

	// unused functions
	$result = print_msg($result, sprintf("%sFunction '%s' appears never to be used.", $ks_space, $fcn_name), $verbosity);

} // end if

// end


return $result;

} // end function manage_unused_function


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function print_msg($result, $msg, $verbosity) {

array_push($result, $msg);


if ($verbosity)
{
	$ks = "    $msg<br>";
	stampa($ks);

}


return $result;

} // end function print_msg


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function stampa($ks) {

$ks = htmlentities($ks);
echo("$ks<br>\n");

}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function strfind($line,$tag) {

$ind = strpos($line,$tag);

if ($ind !== False)
{
// 	$z1 = htmlentities($line);
// 	$z2 = htmlentities($tag);
// 	echo "'$z2' trovato in '$z1' in posizione $ind!<br>\n";
}

return $ind;
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function fcn_strpos($line,$sep) {

$temp = preg_split("/$sep/", $line, -1, PREG_SPLIT_OFFSET_CAPTURE);

$ind = false;
$first = true;
for ($id = 1;$id < count($temp); $id++)
{
	$substr = $temp[$id];
	
	$val = $substr[1]-strlen($sep);
	if ($first)
	{
		$first = false;
		$ind = Array($val);
	}
	else
	{
		array_push($ind,$val);
	}
}

return $ind;

} // end function fcn_strpos


?>
