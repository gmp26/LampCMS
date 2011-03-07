<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms;

use \Lampcms\Forms\Form;

/**
 * This abstract class is responsible for generating
 * an output page.
 *
 * This is an abstract class and must be extended by the
 * actual controller class
 *
 * The controller class must implement the main() method which
 * will be called by this class automatically
 * after some important pre-processing and initialization
 * have taken place: for example that required variables have
 * all been submitted (if any)
 * Also if controller has a specific $permission
 * set as instance variable
 * this class will check that the current user
 * (represented as Viewer object)
 * has required access level to perform this 'permission'
 * an ACL is used for permission check and is based on user group membership
 * which is called 'role' in ACL jargon.
 *
 * @author Dmitri Snytkine
 *
 */
abstract class WebPage extends Base
{

	/**
	 * HTTP Response code to send
	 * the value of 200 is default
	 * This is usefull only when handling certain
	 * exceptions that may indicate a '404 not found' error
	 * in which case we pass the 404 as error code
	 * of exception and then can set the http 404 response
	 *
	 * @var int
	 */
	protected $httpCode = 200;


	/**
	 * Array of required GET or POST
	 * parameters.
	 * These must not be empty
	 *
	 * @var array
	 */
	protected $aRequired = array();



	/**
	 * Object representing Array of QUERY_STRING params
	 * this is GET or POST array
	 *
	 * @var object of type Request
	 */
	protected $oRequest;

	/**
	 * Flag indicates that
	 * the page being rendered for a mobile
	 * device. This means content should
	 * be in a short format - titles only
	 * for a list of articles, etc.
	 *
	 * @var bool
	 */
	protected $isMobile = false;


	/**
	 * Array holds
	 * links generated by pager class
	 *
	 * @var
	 */
	protected $arrPagerLinks;

	/**
	 * Links generated by Paginator
	 *
	 * @var string html to show pagination links
	 */
	protected $pagerLinks = '';


	/**
	 * Flag indicates that REQUEST_METHOD
	 * MUST be POST
	 *
	 * @var bool
	 */
	protected $bRequirePost = false;


	/**
	 * The section is added to generated xml file
	 * and helps a template to make
	 * title of the section a non-link,
	 * for example if user is on 'news' section
	 * then then 'news' should not be a link but
	 * instead a visually 'active' div
	 * @var string
	 */
	protected $section = 'home';

	/**
	 * extra javascript(s) to add to this page
	 * if class extending this class has this property,
	 * then value will be added to the bottom
	 * of the page as value of script tag
	 *
	 * @var mixed string or array of strings
	 */
	protected $lastJs;

	/**
	 * Extra css files to add for the page
	 *
	 * @var mixed string (path to .css file) or
	 * array of such strings
	 */
	protected $extraCss;


	/**
	 * Special type of permission check where we don't
	 * need to check the specific permission but only
	 * require the user to be logged in. This is faster
	 * than a full Access Control check.
	 *
	 * @var bool
	 */
	protected $membersOnly = false;

	/**
	 * Flag indicates that
	 * we require to validate a form token
	 *
	 * @todo remove this, no longer using it here,
	 * now only in Form class
	 *
	 * @var bool
	 */
	protected $requireToken = false;

	/**
	 * Name of template dir
	 * for mobile output it should
	 * be dynamically chaned to 'mobile'
	 * it can also be changed to 'tablet'
	 * for tablet screens
	 *
	 * @var string
	 */
	protected $tplDir = 'www';

	/**
	 *
	 * This is for skinning/styling support
	 * right now we only have 1 style,
	 * it has id = 1
	 * @var mixed int | numeric string
	 */
	protected $styleID = '1';

	/**
	 *
	 * layoutID 1 means 1-pane page: no nav div, just one main div
	 * layoutID 2 means 2-pane page: main div and nav div
	 *
	 * @var mixed int | numeric string
	 */
	protected $layoutID = '2';

	/**
	 * Array of replacement vars
	 * for the tplMain template
	 *
	 * @var array
	 */
	protected $aPageVars;


	/**
	 * Constructor
	 * @return
	 * @param array $aQ array or GET or POST params
	 * @param string $strVirtual[optional]
	 */
	public function __construct(Registry $oRegistry, Request $oRequest = null)
	{
		$this->oRegistry = $oRegistry;


		$this->oRequest = (null !== $oRequest) ? $oRequest : $oRegistry->Request;
		d('cp');

		$this->initParams()
		->setTemplateDir()
		->initViewerObject()
		->loginByFacebookCookie()
		->loginByGfcCookie()
		->loginBySid()
		//->initLangs()
		->initPageVars()
		->addJoinForm();

		Cookie::sendFirstVisitCookie();

		try {
			$this->checkLoginStatus()
			->checkAccessPermission()
			->main();

		} catch(Exception $e) {

			$this->handleException($e);
		}

	} // end constructor



	/**
	 * Checks the access permissions for current page
	 * based on values of $this->bMembersOnly,
	 * $this->bGuestsOnly and logged in status
	 * For example, if page is available only
	 * to logged in users, the exception will be
	 * throws in guest tries to access it
	 *
	 * @return object $this
	 *
	 * @throws LampcmsException if access level
	 * error is detected
	 */
	protected function checkLoginStatus()
	{
		if ($this->membersOnly && !$this->isLoggedIn()) {
			d('cp must login');
			throw new MustLoginException('You must login to access this page');
		}

		return $this;
	}


	/**
	 * Sets SESSION['oViewer']
	 * and then points
	 * $this->oViewer object to it
	 * It is set even if user is not logged in
	 * in which case it will be just an
	 * object with default values from USERS table
	 *
	 * @return object $this
	 */
	protected function initViewerObject()
	{

		if(empty($_SESSION['oViewer'])){
			$_SESSION['oViewer'] = User::factory($this->oRegistry);
			$_SESSION['oViewer']->setTimezone();
			d('oViewer new: '.print_r($_SESSION['oViewer']->getArrayCopy(), 1));
			/**
			 * Send referrer cookie if necessary
			 */
			Cookie::sendRefferrerCookie();
		}

		$this->oRegistry->Viewer = $_SESSION['oViewer'];

		return $this;
	}


	/**
	 * Magic method
	 *
	 * @return
	 */
	public function __toString()
	{
		d('cp');
		return $this->getResult();
	}


	/**
	 * Must be implemented in sub-class
	 * this should contain the main
	 * logic of class.
	 * The purpose of this method is populate
	 * the aPageVars array
	 *
	 * @return
	 */
	abstract protected function main();


	/**
	 * Check Request object for required params
	 * as well as for required form token
	 *
	 * @return object $this
	 */
	protected function initParams(){

		if ($this->bRequirePost && ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'])) ) {
			throw new Exception('POST method required');
		}
			
		$this->oRequest->setRequired($this->aRequired)->checkRequired();

		if(true === $this->requireToken){
			\Lampcms\Forms\Form::validateToken($this->oRegistry);
		}

		d('cp');

		return $this;
	}


	/**
	 * Setup initial
	 * global variables,
	 *
	 *
	 * @return object $this
	 */
	protected function initPageVars()
	{
		if (Request::isAjax() ) {
			d('special case: '.$this->oRequest['a'].' isAjax ');

			return $this;
		}

		$this->aPageVars = \tplMain::getVars();
			
		$oIni = $this->oRegistry->Ini;
		$this->aPageVars['site_title'] = $oIni->SITE_TITLE;
		$this->aPageVars['site_url'] = $oIni->SITE_URL;
		$this->aPageVars['site_description'] = $oIni->SITE_NAME;
		$this->aPageVars['layoutID'] = $this->layoutID;
			
			
		/**
		 * @todo later can change to something like
		 * $this->oRegistrty->Viewer->getStyleID()
		 * and also use CSS_SITE prefix or something like that
		 */
		$this->aPageVars['main_css'] = '/style/'.STYLE_ID.'/'.VTEMPLATES_DIR.'/main.css';

		if('' !== $gfcID = $oIni->GFC_ID){
			$this->addGFCCode($gfcID);
		}

		$aFacebookConf = $oIni->getSection('FACEBOOK');

		if(!empty($aFacebookConf)){
			if(!empty($aFacebookConf['APP_ID'])){
				$this->addMetaTag('fbappid', $aFacebookConf['APP_ID']);
			}
			if(!empty($aFacebookConf['EXTENDED_PERMS'])){
				$this->addMetaTag('fbperms', $aFacebookConf['EXTENDED_PERMS']);
			}

			$this->addFacebookJs($aFacebookConf['APP_ID']);
		}

		$this->aPageVars['session_uid'] = $this->oRegistry->Viewer->getUid();
		$this->aPageVars['role'] = $this->oRegistry->Viewer->getRoleId();
		$this->aPageVars['rep'] = $this->oRegistry->Viewer->getReputation();
		$this->aPageVars['version_id'] = Form::generateToken();

		/**
		 * @todo
		 *  also add twitter id or username or just 'yes'
		 *  of viewer so that we know viewer has twitter account
		 *  and is capable of using twitter from our API
		 *  Also we can ask use to add Twitter account
		 *  if we know he does not have one connected yet
		 */

		return $this;
	}


	protected function addGFCCode($gfcID){

		$this->addMetaTag('gfcid', $gfcID);
		$this->aPageVars['gfc_js'] = \tplGfcCode::parse(array($gfcID), false);

		return $this;
	}


	protected function addFacebookJs($appId){


		$this->aPageVars['fb_js'] = \tplFbJs::parse(array($appId), false);

		return $this;
	}

	/**
	 * Add extra meta tag to the page
	 * @param string $tag name of tag
	 * @param string $val value of tag
	 *
	 * @return object $this
	 */
	protected function addMetaTag($tag, $val){
		$meta = CRLF.sprintf('<meta name="%s" content="%s">', $tag, $val);

		$this->aPageVars['extra_metas'] .= $meta;

		return $this;
	}



	/**
	 * If user session did not
	 * contain data that allowed to
	 * treat user as logged in, then
	 * try to login user by uid/sid cookies
	 * This will work if user has previously
	 * logged in and selected the 'remember me'
	 * check box.
	 *
	 * @return object $this OR redirects back
	 * to the same page but with SESSION setup
	 * with user data, so user will be detected as logged-in
	 * after the redirect
	 */
	protected function loginBySid()
	{
		if ($this->isLoggedIn()) {
			d('cp');
			return $this;
		}

		if (!isset($_COOKIE) || !isset($_COOKIE['uid']) || !isset($_COOKIE['sid'])) {
			d('$_COOKIE: '.print_r($_COOKIE, 1));

			return $this;
		}

		try {
			$oCheckLogin = new CookieAuth($this->oRegistry);
			$oUser = $oCheckLogin->authByCookie();
			d('aResult: '.print_r($oUser->getArrayCopy(), 1));

		} catch(CookieAuthException $e) {
			e('LampcmsError: login by sid failed with message: '.$e->getMessage());
			Cookie::delete(array('uid'));

			return $this;
		}

		/**
		 * Login OK
		 * used to also
		 * ->setUserTimezone($this->oViewer)
		 * but its not necessary because user
		 *  will be redirected anyway
		 */
		$this->processLogin($oUser)
		->updateLastLogin(null, null, 'cookie');

		return $this;

	}


	/**
	 * Login with Google Friend Connect cookie
	 * fcauth
	 */
	protected function loginByGfcCookie()
	{
		if ($this->isLoggedIn() || 'logout' === $this->oRequest['a']) {
			d('cp');
			return $this;
		}

		$GfcSiteID = $this->oRegistry->Ini->GFC_ID;
		if(empty($GfcSiteID)){
			d('not using friend connect');
			return $this;
		}

		try{
			$oGfc = new ExternalAuthGfc($this->oRegistry, $GfcSiteID);
			$oViewer = $oGfc->getUserObject();
		} catch(GFCAuthException $e){

			d('Auth by GFC cookie failed '.$e->getMessage().' '.$e->getFile().' '.$e->getLine());

			return $this;
		}

		$this->processLogin($oViewer)
		->updateLastLogin(null, null, 'fcauth');

		return $this;

	}


	/**
	 * Authenticate user if user has fbc_ cookie
	 * This cookie is set right after user clicks
	 * on login with facebook button
	 *
	 * @return object $this
	 */
	protected function loginByFacebookCookie()
	{
		if ($this->isLoggedIn() || 'logout' === $this->oRequest['a']) {
			d('cp');
			return $this;
		}

		d('cp');
		try{
			$oViewer = ExternalAuthFb::getUserObject($this->oRegistry);
			d('got $oViewer: '.print_r($oViewer->getArrayCopy(), 1));
			$this->processLogin($oViewer)
			->updateLastLogin(null, null, 'facebook');

			d('logged in facebook user: '.$this->oRegistry->Viewer->getUid());

		} catch (FacebookAuthException $e){

			d('Facebook login failed. '.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
		}

		return $this;
	}


	protected function processLogin(User $oUser, $bResetSession = false)
	{

		d('protessing user hashCode: '.$oUser->hashCode().' userHash: '.$oUser->hashCode());

		if(!isset($_SESSION)){
			$_SESSION = array();
		}

		d('cp '.gettype($oUser));

		/**
		 * This give a change for some sort of filter to examine twitter id, twitter name
		 * and possibly disallow the login
		 *
		 */
		if (false === $this->oRegistry->Dispatcher->post($oUser, 'onBeforeUserLogin')) {
			d('cp');
			throw new LoginException('Access denied');
		}

		if($bResetSession){
			session_regenerate_id();
		}

		/**
		 * New way just replace the $_SESSION['oViewer'] with this new object
		 * Not sure if this will actually work, but....
		 * have to try it, otherwise how else can we replace
		 * the type of oViewer from User to the new object
		 * which may now be TwitterUser?
		 *
		 * An alternative would probably be to create a brand new object
		 * of the same type and copy the underlying array to the new object
		 * maybe the clone is a good way to do this?
		 * Not sure if there are any pitfalls in cloning the ArrayObject object
		 *
		 */
		/**
		 * Just a precaution to make sure
		 * descructor will not try to save the object that we no longer need.
		 */
		d('old SESSION oViewer hash code was: '.$_SESSION['oViewer']->hashCode().' old userHash: '.$_SESSION['oViewer']->hashCode());

		/**
		 * Update i_ts_login which
		 * is the last time user was online
		 */
		$oUser->offsetSet('i_ts_login', time());

		$this->oRegistry->Viewer = $_SESSION['oViewer'] = $oUser;
		/**
		 * This is important otherwise
		 * the old stale value is used
		 * when checking isLoggedIn()
		 */
		unset($this->bLoggedIn);

		d('SESSION oViewer is now of type '.$_SESSION['oViewer']->getClass().' hash: '.$_SESSION['oViewer']->hashCode().' new userHash: '.$_SESSION['oViewer']->hashCode());


		$_SESSION['oViewer']->setTimezone();


		/**
		 * Remove navlinks block from
		 * session because
		 * after user logged-in he suppose to see
		 * different links block
		 */
		if(!empty($_SESSION)){
			$_SESSION['navlinks'] = array();
			$_SESSION['login_form'] = null;
			$_SESSION['login_error'] = null;
			//$_SESSION[$this->currentLang] = null;
		}

		/**
		 * Post event notification
		 * so that interested parties may be
		 * notified when user loggs in
		 * No need to pass userData because the
		 * current $this->oViewer object may be examined
		 * (it's available via $this->getObjectViewer()
		 */
		$this->oRegistry->Dispatcher->post( $this, 'onUserLogin' );

		return $this;
	}


	/**
	 * Performs the last step in assembling
	 * the XML object by appending the
	 * Switchaccount form and side menu HTML
	 * if necessary and then
	 * returns the textual representation
	 * of the $this->oDocGlobal object
	 *
	 * @return mixed result of toHTML()
	 */
	public function getResult()
	{

		if(404 === $this->httpCode){
			d('setting 404 error code');
			header("HTTP/1.0 404 Not Found");
		}

		$this->addLoginBlock()->addLastJs()->addExtraCss();

		$tpl = \tplMain::parse($this->aPageVars);
		$scriptTime = ($this->oRegistry->Ini->SHOW_TIMER) ? 'Page generated in '.abs((microtime() - INIT_TIMESTAMP)).' seconds' : '';

		return str_replace('{timer}', $scriptTime, $tpl);

	}


	/**
	 * @todo we shoud probably use oDocGlobal for this
	 * since calls to oPageDoc are passed via __call
	 * to ownerDocument anyway
	 */
	protected function addLastJs()
	{
		d('cp');
		if(!empty($this->lastJs)){
			d('cp');
			foreach ((array)$this->lastJs as $val) {
				$this->aPageVars['last_js'] .= CRLF.sprintf('<script type="text/javascript" src="%s"></script>', $val);
			}
			d('cp');
		}

		return $this;
	}


	/**
	 *
	 * Adds extra stylesheet(s) to the page
	 *
	 * @return object $this
	 */
	protected function addExtraCss()
	{
		d('cp');
		if(!empty($this->extraCss)){
			d('cp');
			foreach ((array)$this->extraCss as $val) {
				$this->aPageVars['extra_css'] .= CRLF.sprintf('<link rel="stylesheet" type="text/css" href="%s">', $val);
			}
		}

		return $this;
	}


	/**
	 * Adds the Login forum or Welcome block
	 *
	 * @return object $this
	 */
	protected function addLoginBlock()
	{
		d('cp');
		$this->aPageVars['header'] = LoginForm::makeWelcomeMenu($this->oRegistry);
		d('cp');

		return $this;
	}


	/**
	 * Formats the exception, adding
	 * additional exception data like backtrace
	 * if running in debug mode
	 * then adds the 'error' under oXS main element
	 *
	 * @return
	 * @param object $e Exception object
	 */
	public function handleException(\Lampcms\Exception $le)
	{
		try {
			d('cp');

			if($le instanceof RedirectException){
				header("Location: ".$le->getMessage(), true, $le->getCode());
				exit;
			}

			if($le instanceof CaptchaLimitException){
				d('Captcha limit reached.');
				/**
				 * @todo add ip to CAPTCHA_HACKS collection
				 *
				 */
			}



			/**
			 * In case of LampcmsAuthException
			 * the value of 'c' attribute in exception
			 * element will be set to "login"
			 * indicating to xslt template that this
			 * is a 'must login' type of exception
			 * and to render the login form
			 *
			 */
			$class = ($le instanceof AuthException) ? 'login' : 'excsl';

			/**
			 * Special case:
			 * the http error code can be
			 * passed in exception as third argument
			 * (in case where there are no second arg,
			 * the second arg must be passed as null)
			 */
			if( 201 < $errCode = $le->getCode()){
				$this->httpCode = (int)$errCode;
			}

			if($le instanceof Lampcms404Exception){
				$this->httpCode = 404;
			}

			e('Exception caught in: '.$le->getFile().' on line: '.$le->getLine().' '.$le->getMessage());

			//$err = ($le instanceof AuthException) ? LoginForm::makeLoginForm($this->oRegistry) : Exception::formatException($le);

			$err = Exception::formatException($le);
			/**
			 * @todo if Login exception then present a login form!
			 *
			 */
			$this->aPageVars['layoutID'] = 1;
			$this->aPageVars['body'] = \tplException::parse(array('message' => $err, 'class' => $class, 'title' => 'La La La...'));

		} catch(\Exception $e) {
			e('Exception object '.$e->getMessage());
			$err = Responder::makeErrorPage($le->getMessage().' in '.$e->getFile());
			exit ($err);
		}

	}



	/**
	 * This method is called from Login and
	 * from wwwOauth, thus its here in one place
	 *
	 * @return array of user profile and welcome html div
	 * if user is logged in, or empty array otherwise
	 */
	protected function makeLoginArray()
	{
		$a = array();
		d('cp');
		if($this->isLoggedIn()){
			$welcome = LoginForm::makeWelcomeMenu($this->oRegistry);
			$a['welcome'] = $welcome;
		}

		d('cp');
		d('a: '.print_r($a, 1));

		return $a;
	}


	/**
	 * Validates the value of form token
	 * passed in form against the one stored in SESSION
	 *
	 * @todo validate (store it first) IP address
	 * of request that it must match ip when token is validate
	 * and throw special type of Exception so that a user will
	 * get explanation that IP address has changed
	 *
	 * @param string $token value as passed in the submitted form
	 * @return true of success
	 * @throws LampcmsException if validation fails
	 */
	protected function validateToken($token = null)
	{

		$message = '';
		$token = ( (null === $token) && !empty($this->oRequest['token']) ) ? $this->oRequest['token'] : $token;

		if(empty($_SESSION['secret'])){
			d("No token in SESSION ".print_r($_SESSION, 1));
			/**
			 * @todo
			 * Translate String
			 */
			$message = 'Form_token_missing';
		} elseif($_SESSION['secret'] !== $token){
			d('session token: '.$_SESSION['secret'].' supplied token: '.$token);
			$message = 'wrong form token';
		}

		if(!empty($message)){

			if(Request::isAjax()){
				Responder::sendJSON(array('exception'=>$message));
			}

			throw new TokenException($message);
		}

		return true;
	}


	/**
	 * Add extra div with "Join" form
	 * where we ask to provide email address
	 * after user joins with external provider
	 *
	 * @return object $this
	 */
	protected function addJoinForm()
	{
		if(!Request::isAjax() && ('remindpwd' !== $this->oRequest['a'])){
			/**
			 * If user opted out of continuing
			 * registration, the special 'dnd' or "Do not disturb"
			 * cookie was set via Javascritp
			 * We will respect that and will not show that same
			 * nagging prompt again
			 *
			 * This cookie is deleted on Logout
			 * @todo set ttl for this cookie to last only a couple of days
			 * so we can keep nagging user again after awhile until user
			 * finally enters email address
			 */
			$cookie = Cookie::get('dnd');
			d('dnd: '.$cookie);
			if(!$cookie){
				$isNewUser = $this->oRegistry->Viewer->isNewUser();
				d('isNewUser: '.$isNewUser.' $this->oRegistry->Viewer: '.print_r($this->oRegistry->Viewer->getArrayCopy(), 1));

				if($this->oRegistry->Viewer instanceof UserExternal){
					$email = $this->oRegistry->Viewer->email;
					d('email: '.var_export($email, true));
					if(empty($email)){
						$sHtml = RegBlock::factory($this->oRegistry)->getBlock();
						d('$sHtml: '.$sHtml);
						$this->aPageVars['extra_html'] = $sHtml;
					}
				}
			}
		}

		return $this;
	}



	/**
	 * Define the location of templates
	 * This is usually used for pointing
	 * to special 'mobile' directory when
	 * we need to serve mobile pages
	 *
	 * @todo something like this:
	 * oRegistry->Viewer->getStyleId().DS.$this->tplDir
	 * where getStyleId will return whatever user
	 * has selected with fallback to default '1'
	 *
	 * @return object $this
	 */
	protected function setTemplateDir(){

		d('setting template dir');
			
		define('STYLE_ID', $this->styleID);
		define('VTEMPLATES_DIR', $this->tplDir);

		return $this;
	}

}
