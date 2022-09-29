# Package Tasks

Describe what to do in each task, step by step.

## First start.
This is only run for the first time after cloning the repository.

* Make sure that you had ever run `npm install` by check at folder **node_modules** must be exists.

### Before start development.
This should be run every time before start modify the files.

* Manually delete **.backup** folder.

## Update packages.
* Run the command `npm outdated` for listing outdated packages.
* Run the command `npm update` to update packages or `npm install [packagename]@latest` to install latest for major version.
* Run the external command `rdbdev assets build --module=RdbCMSA` to do following tasks.
 * Delete **assets** folders.<br>
 * Copy packages from **node_modules** to **assets** folders.<br>
 * Bundle package files.<br>
 * Copy everything from **assets** folder to **public/Modules/RdbCMSA/assets** folder.

### Update version number of packages.
#### Automatic update.
* Run the external command `rdbdev assets writeVersions --module=RdbCMSA` to write the packages version into **ModuleData/ModuleAssets.php** file.
* Open files in **ModuleData/ModuleAssets.php**, **.backup/ModuleData/ModuleAssets.backupxxx.php** where xxx is the date/time of running command.<br>
    Then compare these 2 files to make sure that only version number just changed, otherwise incorrect PHP syntax may cause the website error.

#### Manual update.
Update the version number on these files.

* Update assets version at **ModuleData/ModuleAssets.php** inside `getModuleAssets()`method.

## Editing files.
* Run the external command `rdbdev watch assets --module=RdbCMSA` to automatic copy asset files from **assets** to **public/** folder.

## Before publish or commit.
* Update version number in **Installer.php** file.
* Run update version number again from the command in section **Update version number of packages**.
* Run external pack command `rdbdev pack --module RdbCMSA --packtype=dev prod` to pack files and folders into a zip file.