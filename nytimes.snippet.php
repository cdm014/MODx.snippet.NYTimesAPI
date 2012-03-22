<?php
//*
//$output = "Hello World!";
//*/
/***************
 * New York Times BestSeller Snippets
 * This is a snippet for MODx and is designed
 * to follow proper practices in the seperation
 * of php and html as well as add better customization
 * by allowing placeholders for adding/assigning css classes
 *
 * version 0.3.0
 * Properties for this snippet:
 * bookClass
 * listClass
 * listTitleClass
 * listDateClass
 * bookDetailsClass
 * bookDescriptionClass
 * bookRankClass
 * bookTitleClass
 * bookAuthorClass
 * bookISBNsClass
 * isbnClass
 * isbnLinkClass
 * listNumber
 ***************/
//debug messages
$debug = array();
$debug['debug'] = false;
//Config Information
$listNumber = $modx->getOption('listNumber',$scriptProperties,2);

$apikey = $modx->getOption('api_key',$scriptProperties,"CHANGE ME"); //our api key
$catalog_search_url = "http://innopac.rpl.org/search/{arg}?SEARCH={term}"; //format of catalog search url
//these will be used for styling in the templates
//I'm making them variables so that later I can make them properties of the snippet
$listClass = $modx->getOption('listClass',$scriptProperties,"NYTimesList");
$listTitleClass = $modx->getOption('listTitleclass',$scriptProperties,"NYTimesListTitle");
$listDateClass = $modx->getOption('listDateClass',$scriptProperties,"NYTimesListDate");
$bookClass = $modx->getOption('bookClass',$scriptProperties,"book");
$bookDetailsClass = $modx->getOption('bookDetailsClass',$scriptProperties,"details");
$bookRankClass = $modx->getOption('bookRankClass',$scriptProperties,"rank");
$bookTitleClass = $modx->getOption('bookTitleClass',$scriptProperties,"title");
$bookDescriptionClass = $modx->getOption('bookDescriptionClass',$scriptProperties,"description");
$bookISBNsClass = $modx->getOption('bookISBNsClass',$scriptProperties,"isbns");
$isbnClass = $modx->getOption('isbnClass',$scriptProperties,"isbn");
$isbnLinkClass= $modx->getOption('isbnLinkClass',$scriptProperties,"isbnLink");
$bookAuthorClass = $modx->getOption('bookAuthorClass',$scriptProperties,"author");
$bookJacketClass = $modx->getOption('bookJacketClass',$scriptProperties,"jacket");

$bsOptions['listClass'] = $listClass;
$bsOptions['listTitleClass'] = $listTitleClass;
$bsOptions['listDateClass'] = $listDateClass;
$bsOptions['bookClass'] = $bookClass;
$bsOptions['bookDetailsClass'] = $bookDetailsClass;
$bsOptions['bookRankClass'] = $bookRankClass;
$bsOptions['bookTitleClass'] = $bookTitleClass;
$bsOptions['bookDescriptionClass']= $bookDescriptionClass;
$bsOptions['bookISBNsClass'] = $bookISBNsClass;
$bsOptions['isbnClass'] = $isbnClass;
$bsOptions['isbnLinkClass'] = $isbnLinkClass;
$bsOptions['bookAuthorClass'] = $bookAuthorClass;
$bsOptions['bookJacketClass'] = $bookJacketClass;


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
		if ($apikey == "CHANGE ME") {
			$getnewlist = 0;
			$this->error = "No NYTimes API key. You must change the default setting or pass the api key in the snippet call";
		}
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
		function display(&$debug,$bsOptions ) {
			$modx = $this->modx;
			$output = $this->error;
			//$output = "Hello World";
			$listItems = "";
			if (!$this->error) {	
				foreach ($this->data->results->book as $book) {
					$mainISBN = $book->isbns->isbn[0]->isbn13;
					$jacket_string = "";
					//*
					$jacket_string = "http://contentcafe2.btol.com/ContentCafe/Jacket.aspx?UserID=RPL53198&Password=CC19873&Return=1&Type=S&Value={ISBN}&";
					$jacket_string = preg_replace("/{ISBN}/",$mainISBN,$jacket_string);
					//*/
					$ISBNstring = '';
					foreach($book->isbns->isbn as $isbn) {
						$debug['isbns'][] = (string)$isbn->isbn13;
						$isbnlink = preg_replace("/{arg}/",$this->isbn_arg,$this->linkurl);
						$isbnlink = preg_replace("/{term}/",(string) $isbn->isbn13,$isbnlink);
						
						$ISBNstring .= $modx->getChunk('BestSeller_isbn_template',array(
							'isbn.div.class' => $bsOptions['isbnClass'],
							'isbn.link.text' => (string)$isbn->isbn13,
							'isbn.link.url' => $isbnlink
							
							
							
						));
						
					}
					//populate all the placeholders in the Item Template
					$listItems .= $modx->getChunk('BestSeller_Item_template',array(
						'book.jacket.url' => $jacket_string,
						'book.jacket.classes' => $bsOptions['bookJacketClass'],
						'book.div.classes' => $bsOptions['bookClass'],
						'book.details.div.classes' => $bsOptions['bookDetailsClass'],
						'book.rank.classes' => $bsOptions['bookRankClass'],
						'book.title.classes' => $bsOptions['bookTitleClass'],
						'book.description.classes' => $bsOptions['bookDescriptionClass'],
						'book.isbns.div.classes' => $bsOptions['bookISBNsClass'],
						'book.isbns' => $ISBNstring,
						'book.rank.text' => (string)$book->rank,
						'book.title.text' => (string)$book->book_details->book_detail[0]->title,
						'book.author.text'=> (string)$book->book_details->book_detail[0]->author,
						'book.author.classes' => $bsOptions['bookAuthorClass'],
						'book.description.text' =>(string)$book->book_details->book_detail[0]->description
					
					) );
				}
				$output = $modx->getChunk('BestSeller_list_template', array(
					'list.div.classes' => $bsOptions['listClass'],
					'list.title.classes' => $bsOptions['listTitleClass'],
					'list.date.classes' => $bsOptions['listDateClass'],
					'list.items' => $listItems,
					'list.title.text' => preg_replace("/-/", " ", $this->list_name),
					'list.date.text' => $this->date->format("F d, Y")
				));	
			} else {
				$output = $this->error;
			}
			return $output;

		
		}
	}
	
	
}
$listNumber = $modx->getOption('listNumber',$scriptProperties,2);
$myList = new NYTimes($listNumber, $apikey, $catalog_search_url, "t", "i",$modx); //pull hardcover nonfiction
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
/*/

$output .= $myList->display($debug,$bsOptions);

//*/
if ($debug['debug']) {
	$output .= '<pre>'.print_r($debug,true).'</pre>';

}

return $output;