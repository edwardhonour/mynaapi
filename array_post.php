<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
$baseMaleMult = 222.14;
$baseFemaleMult = 229.65;
$ESMult = 1.8;
$bronzeIIMult = 1.01;
$silverIVMult = 1.35;


function getBasePrice($gender,$age,$baseMaleMult,$baseFemaleMult)

{
if ($gender == 'M')
{
	return(round(((0.0083 * $age * $age * $age) - (0.409 * $age * $age) + (7.17 * $age) + $baseMaleMult),2));
}
else
{
	return(round(((0.0083 * $age * $age * $age) - (0.409 * $age * $age) + (7.17 * $age) + $baseFemaleMult),2));
}
}

   $avgPrice = 0;
   $list=array();
   
   $record=array();
   $record['gender']="M";
   $record['age']="24";

   array_push($list,$record);

   $record=array();
   $record['gender']="F";
   $record['age']="44";

   array_push($list,$record);

   $record=array();
   $record['gender']="M";
   $record['age']="52";
   array_push($list,$record);


   $record=array();
   $record['gender']="F";
   $record['age']="18";
   array_push($list,$record);
   
   
   $record=array();
   $record['gender']="M";
   $record['age']="27";
   array_push($list,$record);
   
   for ($x = 0; $x < sizeof($list); $x++)
   {
	$avgPrice = $avgPrice +  getBasePrice($list[$x]['gender'],$list[$x]['age'],$baseMaleMult,$baseFemaleMult);

   }


   echo '<pre>';
   print_r('Total Members = ' . sizeof($list));
   echo '</pre>';

echo '<h1>Bronze II Plan</h1>';
echo '<pre>';
   print_r('Total Price(EE) = $' . round(($avgPrice * $bronzeIIMult),2));
echo '</pre>';
echo '<pre>';
   print_r('Price per person(EE) = $' . round($bronzeIIMult * $avgPrice/sizeof($list),2));
echo '</pre>';
echo '<pre>';
   print_r('Total Price(ES) = $' . round(($bronzeIIMult * $avgPrice * $ESMult),2));
echo '</pre>';
echo '<pre>';
   print_r('Price per person(ES) = $' . round($bronzeIIMult * $avgPrice * $ESMult/sizeof($list),2));
echo '</pre>';

echo '<h1>Silver IV Plan</h1>';
echo '<pre>';
   print_r('Total Price(EE) = $' . round(($avgPrice * $silverIVMult),2));
echo '</pre>';
echo '<pre>';
   print_r('Price per person(EE) = $' . round($silverIVMult * $avgPrice/sizeof($list),2));
echo '</pre>';
echo '<pre>';
   print_r('Total Price(ES) = $' . round(($silverIVMult * $avgPrice * $ESMult),2));
echo '</pre>';
echo '<pre>';
   print_r('Price per person(ES) = $' . round($silverIVMult * $avgPrice * $ESMult/sizeof($list),2));
echo '</pre>';



?>


