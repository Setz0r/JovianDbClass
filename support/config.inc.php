<?PHP
//**************************************************************************************//
//********                                                                      ********//
//********                         CONFIGURATION FILE                           ********//
//********                                                                      ********//
//**************************************************************************************//
////----                                                                          ----////
////---- Description: Global Configuration File                                   ----////
////----                                                                          ----////
////----                                                                          ----////
////----                                                                          ----////
////----------------------------------------------------------------------------------////
////----                                                                          ----////
////---- File: config.site.php                                                    ----////
////---- Desc: The file configures site specific constants that can't be kept     ----////
////----       in the database, such as database connection variables             ----////
////----                                                                          ----////
////----------------------------------------------------------------------------------////
////----                                                                          ----////
////----  Last Updated: 10-19-2011                                                ----////
////----  Updated by: Aaron Snyder                                                ----////
////----                                                                          ----////
////----------------------------------------------------------------------------------////

/*---------------------{ DATABASE CONSTANTS }-------------------*/
define ("DBUSER","root");
define ("DBPASS","4k1r4s4t0!");
define ("LIV_HOST","172.21.0.9");
define ("DEV_HOST","172.21.0.9");
define ("LIV_DB","liv_hkipm");
define ("DEV_DB","dev_hkipm");
define ("UAT_DB","uat_hkipm");
define ("MYSQLSQDRDB","sqdr_orionfilec");

/*---------------------{ STARQUEST CONSTANTS }------------------*/
define ("DBHOSTSQ","DALSQDR\SQLEXPRESS");
define ("DBUSERSQ","sa");
define ("DBPASSSQ","dallas");
define ("STARQUESTDB","OrionFileC");

/*---------------------{ PATH CONSTANTS }-----------------------*/
define ("FQDN","asnyder.hkipm.com");
//define ("ROOT_PATH","/IPM/trunk/"); //TRAILING SLASH REQUIRED
//define ("ROOT_URL","/IPM/trunk/index.php");
//define ("FULLPATH","E:\\apache\\htdocs\\Dev\\developers\\asnyder\\IPM\\trunk\\");

/*---------------------{ SITE DEV MODE  }-----------------------*/
//define("DEVMODE","LIVE");
//define("DEVMODE","UAT");
define("DEVMODE","DEBUG");

/*---------------------{ SITE }---------------------------------*/
// define ("APP_SITE", "canada");

?>