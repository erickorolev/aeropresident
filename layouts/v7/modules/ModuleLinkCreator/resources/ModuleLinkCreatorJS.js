/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

/** @class ModuleLinkCreatorJS */
Vtiger.Class("ModuleLinkCreatorJS", {}, {
    MODULE_NAME: 'ModuleLinkCreator',

    registerEvents: function () {
        var thisInstance = this;
    }
});

jQuery(document).ready(function () {
    var instance = new ModuleLinkCreatorJS();
    instance.registerEvents();
});
