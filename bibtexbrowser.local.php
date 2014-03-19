<?php

// Add the 'thumbnail' option for rendering
@define('USEBIBTHUMBNAIL',0);
@define('BIBTHUMBNAIL','thumbnail');

/** does nothing but calls method display() on the content and use javascript if needed. 
*/
function JsWrapper(&$content) {
  echo $content->display();
  if (BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT) {
    javascript();
  }
}


function bib2links_with_icons(&$bibentry) {
  $result = $bibentry->getBibLink('http://www.vision.ee.ethz.ch/%7emguillau/icons/bib.png');
  // returns an empty string if no pdf present
  $result .= $bibentry->getLink('pdf','http://www.vision.ee.ethz.ch/%7emguillau/icons/pdf.png');
  // returns an empty string if no url present
  $result .= $bibentry->getLink('url','http://www.vision.ee.ethz.ch/%7emguillau/icons/url.png');
  // returns an empty string if no slides present
  $result .= $bibentry->getLink('slides','http://www.vision.ee.ethz.ch/%7emguillau/icons/slides.png');
  // returns an empty string if no poster present
  $result .= $bibentry->getLink('poster','http://www.vision.ee.ethz.ch/%7emguillau/icons/slides.png');
  // Google Scholar ID. empty string if no gsid present
  $result .= $bibentry->getGSLink('http://www.vision.ee.ethz.ch/%7emguillau/icons/google_scholar.png');
  // returns an empty string if no doi present
  $result .= $bibentry->getDoiLink('http://www.vision.ee.ethz.ch/%7emguillau/icons/doi.png');
  $result .= "\n".'<hr style="visibility: hidden; height:0; clear:both;"/>';
  return $result;
}


function MyBiblioStyle(&$bibentry) {
  $title = $bibentry->getTitle();
  $type = $bibentry->getType();

  // later on, all values of $entry will be joined by a comma
  $entry=array();


  // thumbnail
  if (USEBIBTHUMBNAIL && $bibentry->hasField(BIBTHUMBNAIL)) {
    $thumb = '<img class="thumbnail" src="'.$bibentry->getField(BIBTHUMBNAIL).'" alt=""/>';}
  else $thumb = '';

  // title
  // usually in bold: .bibtitle { font-weight:bold; }
  $title = '<span class="bibtitle">'.$title.'</span><br/>'."\n";
  if ($bibentry->hasField('url')) $title = '<a'.(BIBTEXBROWSER_BIB_IN_NEW_WINDOW?' target="_blank" ':'').' href="'.$bibentry->getField("url").'">'.$title.'</a>';
  

  // author
  if ($bibentry->hasField('author')) {
    $coreInfo = $title . '<span class="bibauthor">'.$bibentry->getFormattedAuthorsImproved().'</span>';}
  else $coreInfo = $title;

  // core info usually contains title + author
  $entry[] = $thumb.$coreInfo;

  // now the book title
  $booktitle = '';
  if ($type=="inproceedings") {
      $booktitle = 'In '.$bibentry->getField(BOOKTITLE); }
  if ($type=="incollection") {
      $booktitle = 'Chapter in '.$bibentry->getField(BOOKTITLE);}
  if ($type=="inbook") {
      $booktitle = 'Chapter in '.$bibentry->getField('chapter');}
  if ($type=="article") {
      $booktitle = 'In '.$bibentry->getField("journal");}

  //// we may add the editor names to the booktitle
  $editor='';
  if ($bibentry->hasField(EDITOR)) {
    $editor = $bibentry->getFormattedEditors();
  }
  if ($editor!='') $booktitle .=' ('.$editor.')';
  // end editor section

  // is the booktitle available
  if ($booktitle!='') {
    $entry[] = '<br/><span class="bibbooktitle">'.$booktitle.'</span>';
  }


  $publisher='';
  if ($type=="phdthesis") {
      $publisher = 'PhD thesis, '.$bibentry->getField(SCHOOL);
  }
  if ($type=="mastersthesis") {
      $publisher = 'Master\'s thesis, '.$bibentry->getField(SCHOOL);
  }
  if ($type=="bachelorsthesis") {
      $publisher = 'Bachelor\'s thesis, '.$bibentry->getField(SCHOOL);
  }
  if ($type=="techreport") {
      $publisher = 'Technical report, '.$bibentry->getField("institution");
  }
  
  if ($type=="misc") {
      $publisher = $bibentry->getField('howpublished');
  }

  if ($bibentry->hasField("publisher")) {
    $publisher = $bibentry->getField("publisher");
  }

  if ($publisher!='') $entry[] = '<span class="bibpublisher">'.$publisher.'</span>';


  if ($bibentry->hasField('volume')) $entry[] =  "volume ".$bibentry->getField("volume");


  if ($bibentry->hasField(YEAR)) $entry[] = $bibentry->getYear();

  $result = implode(", ",$entry).'.';

  // some comments (e.g. acceptance rate)?
  if ($bibentry->hasField('comment')) {
      $result .=  " (".$bibentry->getField("comment").")";
  }
  if ($bibentry->hasField('note')) {
      $result .=  " (".$bibentry->getField("note").")";
  }

  // add the Coin URL
  //$result .=  "\n".$bibentry->toCoins();
  $result .=  "<br/>\n";

  return $result;
} // end style function


/** Class to display a bibliography of a page. */
class BibliographyDisplay  {

  var $entries;

  function setDB(&$bibdatabase) { $this->setEntries($bibdatabase->bibdb); }

  /** sets the entries to be shown */
  function setEntries(&$entries) {
    $this->entries = $entries;
  }

  function setTitle($title) { $this->title = $title; return $this; }
  function getTitle() { return @$this->title ; }

  /** Displays a set of bibtex entries in an HTML format */
  function display() {
    uasort($this->entries, 'compare_bib_entry_by_raw_abbrv');
    print_header_layout();
    foreach ($this->entries as $ref => $bib) {
      $bib->setIndex($ref);
      echo $bib->toHTML();
    } // end foreach
    print_footer_layout();
  } // end function
} // end class



?>

