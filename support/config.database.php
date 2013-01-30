<?PHP
/** 
 * IPM Database Config - Opens database connection and creates database constants
 * 
 * @author    Aaron Snyder <aaron.snyder@hki.com>
 * @date      2013-01-17
 * @version   1.7.x
 * @revision  1
 * @package   IPM\Const\Database
 * 
 */

/*------------------{ DATABASE CLASS SETUP }-----------------*/
define ("TABLE_CONFIG",PROJECTDB.".configoptions");
G::V("db")->open(DBUSER,DBPASS,$SERVER);
G::V("db")->setvar("SQL_MODE","NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"); //COMPATIBILITY, DO NOT DELETE
G::V("db")->setdb(PROJECTDB);

//G::V("db")->openODBC(DBUSERSQ,DBPASSSQ,DBHOSTSQ);
//G::V("db")->odbc_setdb(STARQUESTDB);
//G::V("db")->msOpen(DBUSERSQ,DBPASSSQ,DBHOSTSQ,STARQUESTDB);

/*----------------{ LEGACY DATABASE CONNECT }----------------*/
$dbConnOF = @mysql_connect($SERVER,DBUSER,DBPASS) or die("<center>The site database server (".SERVER.") is down for maintenance. Please try logging on later.</center>");
if (PROJECTDB!="" and !@mysql_select_db(PROJECTDB)) die("<center>The site database (".PARTSDATABASE.") is down for maintenance. Please try logging on later.</center>");

/*----------------{ DATABASE TABLE CONSTANTS }-----------------*/
$tableImport = 'dbo.[CANCNTIMPPF]';			//database name
$tableHeader = 'dbo.[CANCNTHEDPF]';			//database name
define ("STARQUEST_IMPORT",'dbo.[CANCNTIMPPF]');
define ("STARQUEST_HEADER",'dbo.[CANCNTHEDPF]');
define ("STARQUEST_PAYMENTS",'dbo.[CANOPNITMPF]');
define ("STARQUEST_CNTIMPPF",'dbo.[CANCNTIMPPF]');
define ("STARQUEST_CNTHEDPF",'dbo.[CANCNTHEDPF]');
define ("STARQUEST_OPNITMPF",'dbo.[CANOPNITMPF]');
define ("STARQUEST_ORDHEDPF","dbo.[CAN.ORDHEDPF]");
define ("STARQUEST_INVHEDPF","dbo.[CANINVHEDPF]");
define ("STARQUEST_ITMMASPF","dbo.[CANITMMASPF]");
define ("STARQUEST_ITMPRCPF","dbo.[CANITMPRCPF]");
define ("STARQUEST_ITMXDSPF","dbo.[CANITMXDSPF]");
define ("STARQUEST_TBLFILPF","dbo.[CAN.TBLFILPF]");
define ("STARQUEST_CTRMASPF","dbo.[CAN.CTRMASPF]");
define ("STARQUEST_NAMADRPF","dbo.[CAN.NAMADRPF]");
define ("STARQUEST_CURMASPF","dbo.[CAN.CURMASPF]");
define ("STARQUEST_ITMSUPPF","dbo.[CANITMSUPPF]");
define ("STARQUEST_CURXRTSP","dbo.[CANCURXRTSP]");
define ("STARQUEST_CNTDETPF","dbo.[CANCNTDETPF]");
define ("STARQUEST_EQMSASPF","dbo.[CANEQMSASPF]");
define ("STARQUEST_SYCOMPPF","dbo.[CANSYCOMPPF]");

define ("TABLE_CNTDETPF",MYSQLSQDRDB.".cntdetpf");
define ("TABLE_CNTHEDPF",MYSQLSQDRDB.".cnthedpf");
define ("TABLE_CNTIMPPF",MYSQLSQDRDB.".cntimppf");
define ("TABLE_CNTSASPF",MYSQLSQDRDB.".cntsaspf");
define ("TABLE_CTRMASPF",MYSQLSQDRDB.".ctrmaspf");
define ("TABLE_CURMASPF",MYSQLSQDRDB.".curmaspf");
define ("TABLE_CURXRTSP",MYSQLSQDRDB.".curxrtsp");
define ("TABLE_CUSMASPF",MYSQLSQDRDB.".cusmaspf");
define ("TABLE_EQMSASPF",MYSQLSQDRDB.".eqmsaspf");
define ("TABLE_HDINVDETCF",MYSQLSQDRDB.".hdinvdetcf");
define ("TABLE_HDINVHEDCF",MYSQLSQDRDB.".hdinvhedcf");
define ("TABLE_HDORDDETCF",MYSQLSQDRDB.".hdorddetcf");
define ("TABLE_HDORDHEDCF",MYSQLSQDRDB.".hdordhedcf");
define ("TABLE_INVHEDPF",MYSQLSQDRDB.".invhedpf");
define ("TABLE_ITMBALPF",MYSQLSQDRDB.".itmbalpf");
define ("TABLE_ITMMASPF",MYSQLSQDRDB.".itmmaspf");
define ("TABLE_ITMPRCPF",MYSQLSQDRDB.".itmprcpf");
define ("TABLE_ITMSUPPF",MYSQLSQDRDB.".itmsuppf");
define ("TABLE_ITMXDSPF",MYSQLSQDRDB.".itmxdspf");
define ("TABLE_MCDST2PF",MYSQLSQDRDB.".mcdst2pf");
define ("TABLE_MCDSTRPF",MYSQLSQDRDB.".mcdstrpf");
define ("TABLE_NAMADRPF",MYSQLSQDRDB.".namadrpf");
define ("TABLE_OPNITMPF",MYSQLSQDRDB.".opnitmpf");
define ("TABLE_OPDDETPF",MYSQLSQDRDB.".opddetpf");
define ("TABLE_ORDHEDPF",MYSQLSQDRDB.".ordhedpf");
define ("TABLE_SUSMASPF",MYSQLSQDRDB.".susmaspf");
define ("TABLE_SYCOMPPF",MYSQLSQDRDB.".sycomppf");
define ("TABLE_TBLFILPF",MYSQLSQDRDB.".tblfilpf");

define ("TABLE_ERRORCODES",PROJECTDB.".errorcodes");
define ("TABLE_ITEMGROUPS",PROJECTDB.".itemgroups");
define ("TABLE_ITEMLINKS",PROJECTDB.".itemlinks");
define ("TABLE_ITEMLIST",PROJECTDB.".itemlist");
define ("TABLE_ITEMMASTER",PROJECTDB.".itmmaspf");
define ("TABLE_ITEMPRICE",PROJECTDB.".itmprcpf");
define ("TABLE_ITEMDESC",PROJECTDB.".itmxdspf");
define ("TABLE_ITEMLISTSUPPORT",PROJECTDB.".itemlistSupport");
define ("TABLE_LINKLIST",PROJECTDB.".linklist");
define ("TABLE_NOTEHISTORY",PROJECTDB.".notehistory");
define ("TABLE_ORDERFORMS",PROJECTDB.".orderforms");
define ("TABLE_ORDERARCHIVE",PROJECTDB.".orderarchive");
define ("TABLE_ORDERFORMTYPES",PROJECTDB.".orderformtypes");
define ("TABLE_ORDERITEMS",PROJECTDB.".orderitems");
define ("TABLE_ORDERTEMPLATEITEMS",PROJECTDB.".ordertemplateitems");
define ("TABLE_ORDERTEMPLATES",PROJECTDB.".ordertemplates");
define ("TABLE_ORDERTMPCATS",PROJECTDB.".ordertmpcats");
define ("TABLE_ORDERTMPGRPS",PROJECTDB.".ordertmpgrps");
define ("TABLE_ORDERTYPES",PROJECTDB.".ordertypes");
define ("TABLE_VENDORGROUPS",PROJECTDB.".vendorgroups");
define ("TABLE_RESTAURANTS",PROJECTDB.".restaurants");
define ("TABLE_DATETYPES",PROJECTDB.".datetypes");
define ("TABLE_ECMDATEHISTORY",PROJECTDB.".ecmdatehistory");
define ("TABLE_EMAILTEMPLATES",PROJECTDB.".emailtemplates");
define ("TABLE_REGIONS",PROJECTDB.".regions");
define ("TABLE_SHIPPINGCOSTS",PROJECTDB.".shippingcosts");
define ("TABLE_USERS",PROJECTDB.".users");
define ("TABLE_OWNERLIST",PROJECTDB.".managers");
define ("TABLE_USERGROUPS",PROJECTDB.".groups");
define ("TABLE_RESTAURANTGROUPS",PROJECTDB.".restaurantgroups");
define ("TABLE_CILAYOUT",PROJECTDB.".cilayout");
define ("TABLE_CONSULTANTS",PROJECTDB.".consultants");
define ("TABLE_FILETYPES",PROJECTDB.".filetypes");
define ("TABLE_FRANCHISEES",PROJECTDB.".franchisees");
define ("TABLE_GRIDSETTINGS",PROJECTDB.".gridsettings");
define ("TABLE_GROUPLIST",PROJECTDB.".grouplist");
define ("TABLE_LAYOUT",PROJECTDB.".layout");
define ("TABLE_LAYOUTITEMS",PROJECTDB.".layoutitems");
define ("TABLE_LEVELS",PROJECTDB.".levels");
define ("TABLE_MANAGERS",PROJECTDB.".managers");
define ("TABLE_MEASUREMENTS",PROJECTDB.".measurements");
define ("TABLE_NEWS",PROJECTDB.".news");
define ("TABLE_EVENTS",PROJECTDB.".events");
define ("TABLE_NOTES",PROJECTDB.".notes");
define ("TABLE_NOTETYPES",PROJECTDB.".notetypes");
define ("TABLE_PRODUCTLIST",PROJECTDB.".productlist");
define ("TABLE_REPORTS",PROJECTDB.".reports");
define ("TABLE_RESTAURANTFILES",PROJECTDB.".restaurantfiles");
define ("TABLE_STATUSES",PROJECTDB.".statuses");
define ("TABLE_TASKLIST",PROJECTDB.".tasklist");
define ("TABLE_VIRTUALTASKLIST",PROJECTDB.".virtualtasklist");
define ("TABLE_VIRTUALPROJECTS",PROJECTDB.".virtualprojects");
define ("TABLE_TASKS",PROJECTDB.".tasks");
define ("TABLE_TASKTYPES",PROJECTDB.".tasktypes");
define ("TABLE_TEMPITEMS",PROJECTDB.".tempitems");
define ("TABLE_TYPES",PROJECTDB.".types");
define ("TABLE_TOOLBOX",PROJECTDB.".toolbox");
define ("TABLE_SECURITY",PROJECTDB.".security");
define ("TABLE_PROJECTS",PROJECTDB.".projects");
define ("TABLE_MODULES",PROJECTDB.".modules");
define ("TABLE_EVENTSYSTEM",PROJECTDB.".eventsystem");
define ("TABLE_KEYWORDS",PROJECTDB.".keywords");
define ("TABLE_NOTEDESC",PROJECTDB.".notedesc");
define ("TABLE_ORIONAREACODES",PROJECTDB.".orionareacodes");
define ("TABLE_WAREHOUSES",PROJECTDB.".warehouses");
define ("TABLE_ORIONPROCESSES",PROJECTDB.".orionprocesses");
define ("TABLE_CRONJOBS",PROJECTDB.".cronjobs");
define ("TABLE_LANGUAGE",PROJECTDB.".language");
define ("TABLE_USERTYPES",PROJECTDB.".usertypes");
define ("TABLE_REMINDEREMAILS",PROJECTDB.".reminderemails");
define ("TABLE_MASTERTEMPLATEUSAGE",PROJECTDB.".mastertemplateusage");
define ("TABLE_GROUPARCHIVE",PROJECTDB.".grouparchive");
define ("TABLE_VIRTUALFILES",PROJECTDB.".virtualfiles");
define ("TABLE_ARROWCHAT_MESSAGE",PROJECTDB.".arrowchatmessage");
define ("TABLE_ARROWCHAT_PRIVACY",PROJECTDB.".arrowchatprivacy");
define ("TABLE_ARROWCHAT_ROOMS",PROJECTDB.".arrowchatrooms");
define ("TABLE_BUGREPORTDATA",PROJECTDB.".bugreportdata");
define ("TABLE_BUGREPORTLIST",PROJECTDB.".bugreportlist");
define ("TABLE_BUGREPORTSTATUSES",PROJECTDB.".bugreportstatuses");
define ("TABLE_BUGREPORTCATEGORIES",PROJECTDB.".bugreportcategories");
define ("TABLE_BUGREPORTPRIORITIES",PROJECTDB.".bugreportpriorities");
define ("TABLE_EMAILARCHIVE",PROJECTDB.".emailarchive");
define ("TABLE_SAVESTATES",PROJECTDB.".savestates");
define ("TABLE_TASKEDITORS",PROJECTDB.".taskeditors");
define ("TABLE_EMAILDISTRO",PROJECTDB.".emaildistro");

define ("TABLE_CONTRACTVIEWERCNTHEDPF",MYSQLSQDRDB.".cnthedpf");
define ("TABLE_CONTRACTVIEWERCNTDETPF",MYSQLSQDRDB.".cntdetpf");
define ("TABLE_CONTRACTVIEWERCNTSASPF",MYSQLSQDRDB.".cntsaspf");
define ("TABLE_CONTRACTVIEWEREQMSASPF",MYSQLSQDRDB.".eqmsaspf");
define ("TABLE_CONTRACTVIEWERTBLFILPF",MYSQLSQDRDB.".tblfilpf");
define ("TABLE_CONTRACTVIEWERLNGTXTPF",MYSQLSQDRDB.".lngtxtpf");
define ("TABLE_CONTRACTVIEWERRLNGTXTPF","sqdr_rugbyr.lngtxtpf");

define ("TABLE_QRADMINSAVEDGROUPS",PROJECTDB.".qradmingroups");
define ("TABLE_QRADMINFILES",PROJECTDB.".qradminfiles");
define ("TABLE_QRADMINTREE",PROJECTDB.".qradmintree");
?>