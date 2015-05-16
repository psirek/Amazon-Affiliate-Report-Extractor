<?php
/**
	Script that will extract confirmed commission earnings from one or more Amazon Affiliate sites 
	and sum the total in any given currency. Based on the test classes
	
	https://github.com/fubralimited/php-oara/blob/master/examples/generic.php
	
	and
	
	https://github.com/fubralimited/php-oara/blob/master/Oara/Test.php

	*** INSTALLATION ***
	Follow installation step 1 - 5 from https://github.com/fubralimited/php-oara, see also below

	1. Create the folder with the clone of the code.

	git clone https://github.com/fubralimited/php-oara.git php-oara

	2. Change the directory to the root of the project

	cd php-oara

	3. Initialise composer

	curl -s https://getcomposer.org/installer | php --
	php composer.phar self-update
	php composer.phar install
	
	5. Rename Credentials.ini.sample to Credentials.ini

  	*** LICENSE ***
    Copyright (C) 2015  Poul Serek
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
**/	  

//Path to php-oara settings file. Place this script in the php-oara/example folder
require realpath(dirname(__FILE__)).'/../settings.php';
    
//Create credentials. This script assumes that the user and password is the same for alle Amazon Affiliate account
//I recommend to create a serviceuser account with limited access to all the accounts with the same email and password
$credentials = array();
$credentials["user"] = "login@email.com";
$credentials["password"] = "password";
$credentials['type'] = "Publisher";
$credentials["cookiesDir"] = "example";
$credentials["cookiesSubDir"] = "Amazon";
$credentials["cookieName"] = "test";
$credentials['networkName'] = "Amazon";

//Set the start and end date
$startDate = new Zend_Date('Apr 1, 2015', null, 'en_US');
$endDate = new Zend_Date();
$endDate->subDay(1); //The day before today

//Commisions from the different Amazon Affiliates will get converted into this currency and a total sum
//will be presented just before the script terminates
$totalCommisionCurrency = "DKK";
$totalCommision = 0;

//Report name
$reportName = "Amazon affiliate report for odd-one-out.serek.eu";

echo "+--".str_repeat('-', strlen($reportName))."+\n";
echo "| ".$reportName." |\n";
echo "+--".str_repeat('-', strlen($reportName))."+\n\n";
echo "Importing commisions from ".$startDate->toString("dd-MM-yyyy")." to ".$endDate->toString("dd-MM-yyyy")."\n";

//Create the following 4 lines for each Amazon Affiliate account you have. 
$credentials["network"] = "us";
$currency = "USD";
$network = Oara_Factory::createInstance($credentials);
$totalCommision += ReportExtractor::currency($currency, $totalCommisionCurrency, ReportExtractor::Extract($network, "Amazon ".strtoupper($credentials["network"]), $currency, $startDate, $endDate));

$credentials["network"] = "de";
$currency = "EUR";
$network = Oara_Factory::createInstance($credentials);
$totalCommision += ReportExtractor::currency($currency, $totalCommisionCurrency, ReportExtractor::Extract($network, "Amazon ".strtoupper($credentials["network"]), $currency, $startDate, $endDate));

$credentials["network"] = "uk";
$currency = "GBP";
$network = Oara_Factory::createInstance($credentials);
$totalCommision += ReportExtractor::currency($currency, $totalCommisionCurrency, ReportExtractor::Extract($network, "Amazon ".strtoupper($credentials["network"]), $currency, $startDate, $endDate));

$credentials["network"] = "fr";
$currency = "EUR";
$network = Oara_Factory::createInstance($credentials);
$totalCommision += ReportExtractor::currency($currency, $totalCommisionCurrency, ReportExtractor::Extract($network, "Amazon ".strtoupper($credentials["network"]), $currency, $startDate, $endDate));

echo "Total earnings: ".floor($totalCommision)." ".$totalCommisionCurrency."\n";


/**
 * Report extractor
 *
 * @author     Poul Serek, based on the work of Carlos Morillo Merino (https://github.com/fubralimited/php-oara/blob/master/Oara/Test.php)
 *
 */
class ReportExtractor {
	public static function Extract($network, $title, $currency, $startDate, $endDate) {
		
		$totalCommision = 0;

		if ($network->checkConnection()) {
			$merchantList = $network->getMerchantList();

			$merchantIdList = array();
			foreach ($merchantList as $merchant) {
				$merchantIdList[] = $merchant['cid'];
			}
			
			$merchantMap = array();
			foreach ($merchantList as $merchant){
				$merchantMap[$merchant['name']] = $merchant['cid'];
			}
	
			if (!empty($merchantIdList)) {				
				echo "\n*** ".$title." ***\n";

				$transactionList = $network->getTransactionList($merchantIdList, $startDate, $endDate, $merchantMap);
				
				//Sort the array date DESC
				usort($transactionList, function($a, $b) {
    					return strcmp($b['date'],$a['date']);
				});	
				
				echo "-----------------------------------------\n";
				echo "Date\t\tAmount\tStatus\n";
				echo "-----------------------------------------\n";
				
				foreach ($transactionList as $transaction) {
					$paymentDate = new Zend_Date($transaction['date'], "yyyy-MM-dd");
					
					if ($paymentDate->compare($startDate) >= 0 && $paymentDate->compare($endDate) <= 0) {
						echo substr($transaction['date'], 0, 10)."\t".$transaction['commission']."\t".$transaction['status']."\n";
						if ($transaction['status'] == "confirmed"){
							$totalCommision += $transaction['commission'];
						}
					}
				}
				
				echo "\nTotal confirmed commision: ".$totalCommision." ".$currency."\n";
			}

		} else {
			echo "Error connecting to the network, check credentials\n\n";
		}
		
		return $totalCommision;
	}
	
	public static function currency($from, $to, $amount)
	{
	   if($amount == 0) return 0;
	   $content = file_get_contents('https://www.google.com/finance/converter?a='.$amount.'&from='.$from.'&to='.$to);
	
	   $doc = new DOMDocument;
	   @$doc->loadHTML($content);
	   $xpath = new DOMXpath($doc);
	
	   $result = $xpath->query('//*[@id="currency_converter_result"]/span')->item(0)->nodeValue;
	
	   return str_replace(' '.$to, '', $result);
	}
}
