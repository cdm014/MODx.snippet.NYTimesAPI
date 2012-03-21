<?php
//*
$output = "Hello World!";
//*/
/***************
 * New York Times BestSeller Snippets
 * This is a snippet for MODx and is designed
 * to follow proper practices in the seperation
 * of php and html as well as add better customization
 * by allowing placeholders for adding/assigning css classes
 *
 * version 0.0.1
 ***************/
//debug messages
$debug = array();
$debug['debug'] = true;
//Config Information
$apikey = "2d26418b92e8de9246857b019df5fce2:13:57859835"; //our api key
$catalog_search_url = "http://innopac.rpl.org/search/{arg}?SEARCH={term}"; //format of catalog search url
//these will be used for styling in the templates
//I'm making them variables so that later I can make them properties of the snippet
$listClass = "NYTimesList";
$listTitleClass = "NYTimesListTitle";
$listDateClass = "NYTimesListDate";

$bookClass = "book";
$bookDetailsClass = "details";
$bookRankClass = "rank";
$bookTitleClass = "title";
$bookDescriptionClass = "description";
$bookISBNsClass = "isbns";
$isbnClass = "isbn";
$isbnLinkClass="isbnLink";
$debug['test'] = 'test message';
if (!class_exists("NYTimes")) {
	class NYTimes {
		var $date;
		var $list_name;
		var $api_key;
		var $data;
		var $error;
		var $linkurl;
		var $title_arg;
		var $isbn_arg;
		var $modx;
		
		function __construct( $list_number, $keyval, $link_string, $titleArgument, $isbnArgument, &$modx) {
			$this->date = date_create(); //assume we want the most recent list
			$this->api_key = $keyval; //pass in our key
			$this->error = NULL;
			$this->linkurl = $link_string; //pass in the link format
			$this->title_arg = $titleArgument;
			$this->isbn_arg = $isbnArgument;
			$this->setlist($list_number);
			$debug['constructor message'] = "NYTimes Constructor called";
			$this->modx = $modx;
		}
		
		function setlist($list_number) {
			/*****
			 * this function is here so that I don't have to remember the list names
			 * after determining the correct list name, it will get the list to store
			 * in the data member of the class.
			 *****/
			if (!$list_number) {
				$list_number = 0;
			}
			switch($list_number) {
				case 1:
					$this->list_name = "Hardcover-Fiction";
					break;
				case 2:
					$this->list_name = "Hardcover-Nonfiction";
					break;
				case 3:
					$this->list_name =  "Hardcover-Advice";
					break;
				case 4:
					$this->list_name =  "Paperback-Nonfiction";
					break;
				case 5:
					$this->list_name =  "Paperback-Advice";
					break;
				case 6:
					$this->list_name =  "Trade-Fiction-Paperback";
					break;
				case 7:
					$this->list_name =  "Picture-Books";
					break;
				case 8:
					$this->list_name =  "Chapter-Books";
					break;
				case 9:
					$this->list_name =  "Paperback-Books";
					break;
				case 10:
					$this->list_name = "Series-Books";
					break;
				case 11:
					$this->list_name =  "Mass-Market-Paperback";
					break;
			}
			$this->getlist();
			
		}
		function getlist() {
		//get the list name
		$list = $this->list_name;
		//get our api-key
		$apikey = $this->api_key;
		//ensure we download at least once
		$getnewlist = 1;
		$daysback = 0;
			//cycle backwards until we have results
			while ($getnewlist) {
				//format the date correctly
				$list_date = $this->date->format("Y-m-d");
				//construct the url and download the file
				$xmlstr = file_get_contents("http://api.nytimes.com/svc/books/v2/lists/$list_date/$list".".xml?api-key=$apikey");
				//turn the file into a simpleXMLElement to make parsing easier when we display it.
				$this->data = new simpleXMLElement ($xmlstr);
				//check to see if we have results from this list
				if ($this->data->num_results == 0) {
					$this->date->modify("- 1 day");
					$daysback++;
				} else {
					$getnewlist = 0;
				}
				if ($daysback > 100) {
					$this->error = "No list data within 100 days before selected date";
					$getnewlist = 0;
				}
			}
		}
		function display(&$debug ) {
			$modx = $this->modx;
			$output = "Hello World";
			$listItems = "";
			
			foreach ($this->data->results->book as $book) {
				$ISBNstring = '';
				foreach($book->isbns->isbn as $isbn) {
					$debug[] = (string)$isbn->isbn13;
					$ISBNstring .= $modx->getChunk('BestSeller_isbn_template',array(
						'isbn.div.class' => $isbnClass,
						'isbn.link.text' => (string)$isbn->isbn13
						
					));
					
				}
			}
			return $output;

		
		}
	}
	
	
}

$myList = new NYTimes(1, $apikey, $rpl_string, "t", "i",$modx); //pull hardcover nonfiction
/*
//Loop through the $myList->data to build the placeholder for the list
$listItems = "";
foreach ($myList->data->results->book as $book) {
	
	$ISBNstring = '';
	//first we have to populate the isbns
	foreach ($book->isbns->isbn as $isbn) {
		$ISBNstring .= $modx->getChunk('BestSeller_isbn_template',array(
			'isbn.div.class' => $isbnClass,
			'isbn.link.text' => (string)$isbn->isbn13
			
		));
		
	}
	//populate all the placeholders in the Item Template
	$listItems .= $modx->getChunk('BestSeller_Item_template',array(
		'book.div.classes' => $bookClass,
		'book.details.div.classes' => $bookDetailsClass,
		'book.rank.classes' => $bookRankClass,
		'book.title.classes' => $bookTitleClass,
		'book.description.classes' => $bookDescriptionClass,
		'book.isbns.div.classes' => $bookISBNsClass,
		'book.isbns' => $ISBNstring,
		'book.rank.text' => (string)$book->rank,
		'book.title.text' => (string)$book->book_details->book_detail[0]->title,
		
		'book.description.text' =>(string)$book->book_details->book_detail[0]->description
	
) );
	
}


//*
//now that I have the individual items built I can put them inside the list template
$output = $modx->getChunk('BestSeller_list_template', array(
	'list.div.classes' => $listClass,
	'list.title.classes' => $listTitleClass,
	'list.date.classes' => $listDateClass,
	'list.items' => $listItems,
	'list.title.text' => preg_replace("/-/", " ", $myList->list_name),
	'list.date.text' => $myList->date->format("F d, Y")
));	
//*/
$output .= $myList->display($debug);

//*/

	$output .= '<pre>'.print_r($debug,true).'</pre>';



return $output;
 
 