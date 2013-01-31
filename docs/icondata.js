
        Docs.iconsStr = '{'+
                '"IPM_db":"icon-cls",'+
          
                '"IPM_db_engine":"icon-cls",'+
          
                '"IPM_db_plugin_mysql":"icon-cls",'+
          
                '"IPM_db_plugin_sqlsrv":"icon-cls",'+
          
                '"aIPM_db_engine":"icon-cls",'+
          
                '"aIPM_db_plugin":"icon-cls",'+
          
                '"iIPM_db_plugin":"icon-cls",'+
          '}';
        Docs.iconsStr = Docs.iconsStr.replace(/\"\,\}/g,'"}');
        eval('Docs.icons = '+Docs.iconsStr+';');
    
