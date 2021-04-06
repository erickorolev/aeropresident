<?php

chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'vtlib/Vtiger/Package.php';
include_once 'includes/main/WebUI.php';

include_once 'include/Webservices/Utils.php';


$repacker = new SPRepackModules();
$repacker->repack();


class SPRepackModules {
    
    private $skipModules = ['Dashboard', 'Events', 'Webmails'];
    private $projectsBundle = ['Project', 'ProjectMilestone', 'ProjectTask'];
    private $dismissManifest = false;
    
    //SalesPlatform.ru: set "--manifest dismiss" in cli if you need don't update manifest in zip
    public function repack() {
        $options = getopt("v::",array("manifest:"));
        
        if ($options['manifest'] == 'dismiss') {
            $this->dismissManifest = true;
        }
        
        SPRepackLogger::log("Running repack all modules");
        $package = new Vtiger_Package();
        foreach(Vtiger_Module_Model::getAll() as $module) {
            if($this->isRepackableModule($module->getName())) {
                SPRepackLogger::log("Start repack " . $module->getName() . " module");
                $packagePath = $this->searchPackagePath($module->getName());
                if(file_exists($packagePath)) { 
                    if ($this->dismissManifest) {
                        $result = file_get_contents('zip://' . $packagePath . '#manifest.xml'); 
                        
                        file_put_contents('tmp.xml', $result);
                    }
                    unlink($packagePath); 
                    $packageName = $this->getPackageName($module->getName()); 
                    $package->export($module, $packagePath, $packageName, false, $this->dismissManifest); 

                    if(file_exists('tmp.xml')) {
                        unlink('tmp.xml');
                    }
                    SPRepackLogger::log("Repack of " . $module->getName() . " finished"); 
                    continue; 
                }
                
                SPRepackLogger::log("Not found zip package of module " . $module->getName() . ". No need repack");
            }
        }
        
        SPRepackLogger::log("Start repack special modules");
        $this->repackSpecialModules();
        SPRepackLogger::log("End repack special modules");
    }
    
        private function repackSpecialModules() {
        SPRepackLogger::log("Start Projects bundle repack");
        
        $package = new Vtiger_Package();
        $zip = new ZipArchive();
        $status = $zip->open('packages/vtiger/optional/Projects.zip');
        
        if(!$status) {
            SPRepackLogger::log("Cannot open Projects bundle to repack. End Projects repack");
            return;
        }
        

        foreach($this->projectsBundle as $moduleName) {
            SPRepackLogger::log("Repacking " . $moduleName . " in Projects bundle");
            
            $module = Vtiger_Module_Model::getInstance($moduleName);
            $packagePath = $this->searchPackagePath($moduleName);
            if($packagePath == null) {
                $packagePath = 'packages/vtiger/optional/' . $this->getPackageName($moduleName);
            }

            if(file_exists($packagePath)) {
                unlink($packagePath);
            }

            $package->export(
                $module, 
                $packagePath,
                $this->getPackageName($moduleName)
            );

            $zip->addFile($packagePath, $this->getPackageName($moduleName));
            
            unlink($packagePath);
        }
        
        $zip->close();
        
        SPRepackLogger::log("End Projects bundle repack");
    }
    
    private function searchPackagePath($moduleName) {
        foreach($this->getPackagesFolders() as $folderName) {
            $folderPackages = scandir($folderName);
            foreach($folderPackages as $fileName) {
                if($this->isPackageMatch($moduleName, $fileName)) {
                    return $folderName . "/" . $this->getPackageName($moduleName);
                }
            }
        }
        
        return null;
    }
    
    private function getPackageName($moduleName) {
        return $moduleName . '.zip';
    }
    
    private function getPackagesFolders() {
        return [
            'packages/vtiger/mandatory',
            'packages/vtiger/optional',
            'packages/vtiger/marketplace'
        ];
    }
    
    private function isPackageMatch($moduleName, $fileName) {
        return ($this->getPackageName($moduleName) === $fileName);
    }
    
    private function isRepackableModule($moduleName) {
        return !in_array($moduleName, $this->skipModules);
    }
    
}


class SPRepackLogger {
    
    public static function log($message) {
        echo $message . "\n";
    }
}