<?php
if(!defined('APPLICATION')) die();

$PluginInfo['EasyMembersList'] = array(
	'Name' => 'Easy Members List',
	'Description' => 'Show members list in vanilla forums. Link position and allowed users are configurable.',
	'Version' => '0.1.1',
	'RequiredApplications' => array('Vanilla' => '2.1a1'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => FALSE,
	'SettingsUrl' => 'settings/easymemberslist',
	'SettingsPermission' => 'Garden.Settings.Manage',
	'Author' => "Alessandro Miliucci",
	'AuthorEmail' => 'lifeisfoo@gmail.com',
	'AuthorUrl' => 'http://forkwait.net',
	'License' => 'GPL v3'
);

class EasyMembersListPlugin extends Gdn_Plugin{
  //TODO: add others place to show link
  //TODO: role based "show only to" configuration
  //TODO: show a default "page not found" when user is not allowed to see list
  //TODO: remove /members route on disable

  public function SettingsController_EasyMembersList_Create($Sender) {
    $Sender->Permission('Garden.Plugins.Manage');
    $Sender->AddSideMenu();
    $Sender->Title('Easy Members List');
    $ConfigurationModule = new ConfigurationModule($Sender);
    $ConfigurationModule->RenderAll = True;
    $Schema = array( 'Plugins.EasyMembersList.ShowLinkInMenu' => 
		     array('LabelCode' => T('Show members list link in menu'), 
			   'Control' => 'CheckBox', 
			   'Default' => C('Plugins.EasyMembersList.ShowLinkInMenu', '1')
			   ),
		     'Plugins.EasyMembersList.ShowLinkInFlyout' => 
		     array('LabelCode' => T('Show members list link in account option flyout'), 
			   'Control' => 'CheckBox', 
			   'Default' => C('Plugins.EasyMembersList.ShowLinkInFlyout', '1')
			   ),
		     'Plugins.EasyMembersList.ShowToGuests' => 
		     array('LabelCode' => T('Show members list to guest'), 
			   'Control' => 'CheckBox', 
			   'Default' => C('Plugins.EasyMembersList.ShowToGuests', '1')
			   ),
		     'Plugins.EasyMembersList.ShowOnlyToTheseUsers' => 
		     array('LabelCode' => T('Show members list only to these users (comma separated usernames). Guest configuration is not affected by this.'), 
			   'Control' => 'TextBox',
			   'Options' => array('Multiline' => TRUE),
			   'Default' => C('Plugins.EasyMembersList.ShowOnlyToTheseUsers', '')
			   )
		     );
    $ConfigurationModule->Schema($Schema);
    $ConfigurationModule->Initialize();
    $Sender->View = dirname(__FILE__) . DS . 'views' . DS . 'easy_members_list_settings.php';
    $Sender->ConfigurationModule = $ConfigurationModule;
    $Sender->Render();
  }

  //check before show link and before show page
  //check permission in settings
  private function isUserAllowed($Sender){
    $UserName = Gdn::Session()->User->Name;
    if(!$UserName){    //if user is guest check conf and make show/hide
      if(C('Plugins.EasyMembersList.ShowToGuests', '0') == '1'){
	return true;
      }else{
	return false;
      }
    }else{
      //else (user not guest) check if the list is empty (show)
      $ArrUsers = explode(',', C('Plugins.EasyMembersList.ShowOnlyToTheseUsers', ''));
      $ArrUsersTrimmed = array();
      foreach($ArrUsers as $Name){
	$TrimmedName = trim($Name);
	if($TrimmedName != ''){
	  array_push($ArrUsersTrimmed, trim($Name));
	}
      }
      //if list is not empty check if username is in list
      if(count($ArrUsersTrimmed) != 0){
	if(in_array($UserName, $ArrUsersTrimmed)){
	  return true;
	}else{
	  return false;
	}
      }else{//show to all members
	return true;
      }
      //return true => ok, show | false => ko, hide
    }
  }

  public function Base_Render_Before($Sender){//check settings
    if( C('Plugins.EasyMembersList.ShowLinkInMenu', '0') == '1' ){
      if(self::isUserAllowed($Sender)){
	$Sender->Menu->AddLink('Members', T('Members list'), 'members');
      }
    }
  }
  
  public function MeModule_FlyoutMenu_Handler($Sender){
    if( C('Plugins.EasyMembersList.ShowLinkInFlyout', '0') == '1' ){
      if(self::isUserAllowed($Sender)){
	echo Wrap(Anchor(Sprite('SpMembersList').' '.T('Members list'), 'members'), 'li');
      }
    }
  }

  public function PluginController_EasyMembersList_Create($Sender){
    if(self::isUserAllowed($Sender)){
      $Sender->ClearCssFiles();
      $Sender->AddCssFile('style.css');
      $Sender->AddCssFile('/plugins/EasyMembersList/design/easy_members_list.css');
      $Sender->MasterView = 'default';
      
      $Sender->UserData = Gdn::SQL()->Select('User.*')->From('User')->OrderBy('User.Name')->Where('Deleted',false)->Get();
      RoleModel::SetUserRoles($Sender->UserData->Result());
      $Sender->Render(dirname(__FILE__) . DS . 'views' . DS . 'easy_members_list.php');
    }
  }
  
  public function Setup() {
    $this->Structure();
  }
  
  public function Structure() {
    Gdn::Router()->SetRoute('members', '/plugin/EasyMembersList', 'Internal');
  }
  

}
?>
