<?php

class PAGES {


    function __construct() {

    }

    function getTableData($data) {

      $output='[
         {"ID":"1",
         "FIRST_NAME":"Mark",
         "LAST_NAME":"Otto",
         "USERNAME":"mdo"},
         {"ID":"2",
         "FIRST_NAME":"John",
         "LAST_NAME":"Doe",
         "USERNAME":"jdoe"},
         {"ID":"3",
         "FIRST_NAME":"Larry",
         "LAST_NAME":"the Bird",
         "USERNAME":"bird"},
         {"ID":"4",
         "FIRST_NAME":"Bill",
         "LAST_NAME":"Robertson",
         "USERNAME":"brow"},
         {"ID":"5",
         "FIRST_NAME":"Frank",
         "LAST_NAME":"Issacson",
         "USERNAME":"brow"},
         {"ID":"6",
         "FIRST_NAME":"Mary",
         "LAST_NAME":"Umberto",
         "USERNAME":"brow"},
         {"ID":"7",
         "FIRST_NAME":"Lisa",
         "LAST_NAME":"Carlson",
         "USERNAME":"brow"},
         {"ID":"8",
         "FIRST_NAME":"Karen",
         "LAST_NAME":"Cameron",
         "USERNAME":"brow"},
         {"ID":"9",
         "FIRST_NAME":"Michael",
         "LAST_NAME":"Michaels",
         "USERNAME":"brow"},
         {"ID":"10",
         "FIRST_NAME":"Kelly",
         "LAST_NAME":"Smith",
         "USERNAME":"brow"},
         {"ID":"11",
         "FIRST_NAME":"Carl",
         "LAST_NAME":"Jones",
         "USERNAME":"brow"}
       ]';

       return json_decode($output,true);
    }

    function getClaimsData($data) {

      $output='[
         {"ID":"1",
         "CLAIM_DATE":"12/02/2021",
         "PROVIDER_NAME":"DAVID HOCK MD",
         "CITY": "PT WASHINGTON, NY",
         "TOTAL_BILLED":"$2,156.19",
         "TOTAL_APPROVED":"$825.30"}
       ]';

       return json_decode($output,true);
    }

    function getDashboardData($data) {
       $output='{
         "cost_per_unit": "$17.21",
         "cost_per_unit_pct": "12.5%",
         "market_revenue": "$1875.54",
         "market_revenue_pct": "-18.71%",
         "expenses": "$785.62",
         "expenses_pct": "57%",
         "daily_visits": "1,155,187",
         "daily_visits_pct": "17.8%",
         "total_sales": "5,459",
         "open_campaign": "18",
         "revenue": "1,875.54",
         "total_offers": "541",
         "total_revenue": "$7,841.12",
         "total_open_campaign": "17",
         "candlestick": [
             [1590778600000,6529.81, 6540.5, 6523.05, 6533.33],
             [1590884800000,6504.98, 6506, 6504.07, 6506]
         ],
         "recent_customers": [
             {"customer": "Paul J. Friend",
              "phone": "937-330-1634",
              "email": "paul@example.com",
              "city": "New York",
              "date": "07/07/2018",
              "avatar": "assets/images/users/avatar-8.jpg"},
             {"customer": "Brian J. Luellen",
              "phone": "215-302-3376",
              "email": "brian@example.com",
              "city": "New York",
              "date": "07/27/2019",
              "avatar": "assets/images/users/avatar-8.jpg"},
             {"customer": "Kathryn S. Collier",
              "phone": "404-266-3542",
              "email": "kathryn@example.com",
              "city": "Canada",
              "date": "06/30/2019",
              "avatar": "assets/images/users/avatar-8.jpg"},
             {"customer": "Timothy Kauper",
              "phone": "202-454-3322",
              "email": "timothy@example.com",
              "city": "Denmark",
              "date": "07/07/2018",
              "avatar": "assets/images/users/avatar-8.jpg"},
             {"customer": "Zara Raws",
              "phone": "(02) 75 150 665",
              "email": "zara@example.com",
              "city": "Germany",
              "date": "07/15/2018",
              "avatar": "assets/images/users/avatar-8.jpg"}
         ],
         "transactions": [
             {"cc": "4257 **** **** 7852",
              "exp": "11 April 2020",
              "amount": "$79.49",
              "type": "fab fa-cc-visa",
              "name": "Helen Warren"},
             {"cc": "4265 **** **** 0025",
              "exp": "28 Jan 2020",
              "amount": "$1254.00",
              "type": "fab fa-cc-stripe",
              "name": "Kayla Lambie"},
			 {"cc": "5570 **** **** 8547",
              "exp": "12 Dec 2021",
              "amount": "$784.25",
              "type": "fab fa-cc-amazon-pay",
              "name": "Hugo Lavarack"},
             {"cc": "4257 **** **** 7852",
              "exp": "11 April 2020",
              "amount": "$485.24",
              "type": "fab fa-cc-visa",
              "name": "Amber Scurry"},
             {"cc": "4257 **** **** 7852",
              "exp": "11 April 2020",
              "amount": "$79.49",
              "type": "fab fa-cc-visa",
              "name": "Caitlyn Gibney"}
         ]
       }';
       return json_decode($output,true);

    }

}



