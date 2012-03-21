<?php
$caturl = "http://innopac.rpl.org/search/{arg}?SEARCH={term}";
class NYTimes {
	
	var $date;
	var $list_name;
	var $api_key;
	var $data;
	var $error;
	var $caturl;
	var $title_arg;
	var $isbn_arg;

	
	function __construct( $list_number, $keyval, $catalog_string, $title, $isbn) {
		$this->date = date_create();
		
		
		$this->api_key = $keyval;
		$this->error = NULL;
		
		$this->caturl = $catalog_string;
		$this->title_arg = $title;
		$this->isbn_arg = $isbn;
		$this->setlist($list_number);
		
	}
	
	function title_link($title) {
		$arg = $this->title_arg;
		$term = urlencode($title);
		
		$caturl =  preg_replace("/{arg}/", $arg, $this->caturl);
		$caturl = preg_replace("/{term}/", $term, $caturl);
		return "<a href=\"$caturl\">$title</a>";
	}
	
	function isbn_link($isbn) {
		$arg = $this->isbn_arg;
		$term = urlencode($isbn);
		$caturl =  preg_replace("/{arg}/", $arg, $this->caturl);
		$caturl = preg_replace("/{term}/", $term, $caturl);
		return "<a href=\"$caturl\">$isbn</a>";
	}
	
	function setlist($list_number) {
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
	
	function setDate($newDate) {
		$this->date = $newDate;
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
	
	function display() {
		$numargs = func_num_args();
		$title_link = true;
		$isbn_link = true;
		if ($numargs > 0) {
			$max_list = func_get_arg(0);
			$max_list++;
		} elseif ($numargs == 0) {
			$max_list = 16;
		}
		
		if ($numargs > 1) {
			$title_link = func_get_arg(1);
		} 
		if ($numargs > 2) {
			$isbn_link = func_get_arg(2);
		}
		if ($this->error) {
			$output = "<p style=\"color:red\">There was an error getting the list: $this->error </p>";
		} else {
			//get a list title for display
			$lName = $this->list_name;
			$list_title = preg_replace("/-/", " ", $this->list_name);
			
			$output = "<div class=\"NYTimesList $lName\"> <span class=\"list-title\">$list_title</span>\r\n";
			$output .= "<span>".$this->date->format("F d, Y")."</span>";
			foreach ($this->data->results->book as $book) { //loop through each book
				if ($book->rank < $max_list) { //Only get as many as we want
					$output .= "<div class=\"book\" style=\"clear:both\">\r\n";
					$output .= "<div class=\"details\">\r\n";
					$output .= "<div style=\"float:left\">".$book->rank."</div>"; //get the rank
					$output .= "<span>";
					if ($title_link) {
						$output .= $this->title_link($book->book_details->book_detail[0]->title)." by ".$book->book_details->book_detail[0]->author;
					} else {
						$output .= $book->book_details->book_detail[0]->title." by ".$book->book_details->book_detail[0]->author;
					}		
					 //get the title and author
					$output .= "<br />".htmlspecialchars($book->book_details->book_detail[0]->description, ENT_QUOTES)."</span>\r\n";
				$output .= "</div>\r\n";
				$output .= "<div style=\"clear:both\" class=\"isbns\">";
				foreach($book->isbns->isbn as $isbn) {
				//display the isbns (I can link to these in our catalog)
					if ($isbn_link) {
						$output .= "<div class=\"isbn\">".$this->isbn_link($isbn->isbn13)."</div>"; 
					} else {
						$output .= "<div class\"isbn\">".$isbn->isbn13."</div>";
					}
				}
				$output .= "<span style=\"clear:both\">&nbsp;</span>";
				$output .= "</div>\r\n";
			$output .= "</div>\r\n";
		}
	}
			
			
			
		}
	$output .= "</div>\r\n";

	return $output;
	}
}