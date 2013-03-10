<?php

@define(BIBTEXBROWSER_REF_LEFT_QUOTATIONMARK,'&quot;');
@define(BIBTEXBROWSER_REF_RIGHT_QUOTATIONMARK,'&quot;');
@define(BIBTEXBROWSER_REF_UNKNOWN_KEY_TEXT,'Unknown key');
@define(BIBTEXBROWSER_REF_UNKNOWN_KEY_REPLACEMENT,'?');
@define(BIBTEXBROWSER_REF_UNKNOWN_KEY_CLASS,'bibrefbroken');
@define(BIBTEXBROWSER_REF_LEFT_CITATION_BRACKET,'[');
@define(BIBTEXBROWSER_REF_RIGHT_CITATION_BRACKET,']');
@define(BIBTEXBROWSER_REF_CITATION_SEPARATOR,',');
@define(BIBTEXBROWSER_REF_JAVASCRIPT_ENHANCEMENT,1);
@define(BIBTEXBROWSER_REF_CITATION_LINK_CLASS,'bibreflink');
@define(BIBTEXBROWSER_REF_REFERENCE_CLASS,'bibline');
@define(BIBTEXBROWSER_REF_ACTIVE_REFERENCE_CLASS,'bibline-active');


// Load the database without doing anything else
$_GET['library']=1;
include( 'bibtexbrowser.php' );
setDB();

// Keep track of all citations and their reference id (depends on ABBRV_TYPE)
$_GET['keys'] = array();

// Function to create a link for a bibtex entry
function linkify($txt,$a) {
  if ( empty($a) ) { return '<span class="'.BIBTEXBROWSER_REF_UNKNOWN_KEY_CLASS.'"><abbr title="'.$txt.'">'.BIBTEXBROWSER_REF_UNKNOWN_KEY_REPLACEMENT.'</abbr></span>'; }
  return '<a href="#' . $a . '" class="'.BIBTEXBROWSER_REF_CITATION_LINK_CLASS.'"><abbr title="'.$txt.'">' . $a . '</abbr></a>' ;
}

// Function to create a short text overlay
function veryShortAbbrv($bib) {
  $txt  = $bib->getVeryCompactedAuthors();
  $txt .= ", ".BIBTEXBROWSER_REF_LEFT_QUOTATIONMARK.$bib->getTitle().BIBTEXBROWSER_REF_RIGHT_QUOTATIONMARK;
  $txt .= ", ".$bib->getYear();
  return $txt;
}

// Short text for invalid keys
function brokenAbbrv($entry) {
  $txt  = BIBTEXBROWSER_REF_UNKNOWN_KEY_TEXT;
  $txt .= " ".BIBTEXBROWSER_REF_LEFT_QUOTATIONMARK."$entry".BIBTEXBROWSER_REF_RIGHT_QUOTATIONMARK;
  return $txt;
}

// array_map_assoc
function assoc_array_map($callback,$array) {
    $result = array();
    foreach ( $array as $key => $val ) {
        $r = $callback($key,$val);
        $result[] = $r;
    }
    return $result;
}

// Create citations from bibtex entries. One argument per bibtex entry.
/* Example:  As shown in <?php cite("MyBibtexEntry2013","MyOtherRef2013");?> , one can use bibtex within HTML/PHP.
*/
function cite() {
    $DB = $_GET[Q_DB];
    $entries = func_get_args(); // list of bibtex entries to cite
    $refs = array(); // associate: abbrv txt => reference abbrv
    $citations = $_GET['keys']; // existing associations: bibtex entries => reference abbrv
    // process argument list
    foreach ($entries as $entry) {
          $bib = $DB->getEntryByKey($entry);
          if ( empty($bib) ) { // entry not found in database
             $ref = array(); // empty ref for detection by linkify, while getting last with sort()
             $refs[brokenAbbrv($entry)] = $ref;
          } else { // entry exists
            if (ABBRV_TYPE != 'index') { // when not using index as abbrv, simply retrieve the abbrv from the database
                $ref = $bib->getAbbrv();
                $citations[$entry] = $ref;
            } else { // for the index abbrv, check if the entry is already associated with a reference number
              if ( array_key_exists ( $entry , $citations ) ) { // yes: use it
                  $ref = $citations[$entry] ;
              } else { // no: generate a valid reference number and keep track of it
                  $ref = count( $citations ) + 1 ;
                  $citations[$entry] = $ref ;
              }
            }
            $refs[veryShortAbbrv($bib)] = $ref;
          }
    }
    // output references
    asort( $refs ); // sort by abbrv
    $links = assoc_array_map(linkify,$refs);
    echo BIBTEXBROWSER_REF_LEFT_CITATION_BRACKET;
    echo implode(BIBTEXBROWSER_REF_CITATION_SEPARATOR,$links);
    echo BIBTEXBROWSER_REF_RIGHT_CITATION_BRACKET;
    $_GET['keys']=$citations;
}

// Function to print out the table/list of references
function make_bibliography() {
    $bibfile = $_GET[Q_FILE]; // save bibfilename before resetting $_GET
    $keys = json_encode(array_flip($_GET['keys']));
    $_GET = array();
    $_GET['bib'] = $bibfile;
    $_GET['bibliography'] = 1; // also sets $_GET['assoc_keys']=1
    $_GET['keys'] = $keys;
    //print_r($_GET);
    include( 'bibtexbrowser.php' );
    if (BIBTEXBROWSER_REF_JAVASCRIPT_ENHANCEMENT) {
?>

<script type="text/javascript" ><!--
updateCitation = function () {
    var hash = window.location.hash.slice(1); //hash to string
    var refclass = <?php echo '".'.BIBTEXBROWSER_REF_REFERENCE_CLASS.'"'; ?>;
    var actclass = <?php echo '"'.BIBTEXBROWSER_REF_ACTIVE_REFERENCE_CLASS.'"'; ?>;
    $(refclass).each(function() {$(this).removeClass(actclass);}); // clean any active references
    if (hash) {
        $('a[name='+hash+']').parents(refclass).each(function() {$(this).addClass(actclass);}); // find the parent with class refclass and add actclass.
    }
};
$(window).bind('hashchange',updateCitation); //detect hash change
updateCitation();
--></script>

<?php
} // end if JS enhancement
} // end make_bibliography()

?>
