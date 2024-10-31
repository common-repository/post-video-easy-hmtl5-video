
/**
 * 
 * @return
 */
function HTML5Video(defaults)
{
	//debugging variables.
	this.debugging = true;
	this.debuggingElementId;
	
	//keys used for detecting the different platforms.
	this.osKeyBlackberry = ['blackberry'];
	this.osKeyAndroid = ['android'];
	this.osKeyiOS = ['webos','iphone','ipod','ipad'];
	
	//in case you hate a particular platform
	this.detectIOS = true;
	this.detectBlackberry = true;
	this.detectAndroid = true;
	
	//this property can be toggled to use flash and HTML5 only as a fallback.
	this.useFlashFirst = false;
	
	//you can set the flash requirements as well if you are using the Adobe flash detection plugin.
	this.flashMajorVersion = 8;
	this.flashMinorVersion = 0;
	this.flashRequiredRevision = 0;
	this.hasFlash = false;
	
	//this is a required field. you must specificy the ID of the DIV or element that you want to inject the video into
	this.outputElementId;
	
	//these are all required properties for the mobile devices
	this.mobileVideoObject;
	this.mobileH264;
	this.mobileAdditionalVideos = {};
	this.mobilePosterImage;
	this.androidPosterImage;
	
	//these are the required properties for serving to a desktop platform
	this.desktopVideoObject;
	this.desktopH264;
	this.desktopAdditionalVideos = {};
	this.desktopFlashObject;
	this.desktopPosterImage;
	
	if(defaults != null)
	{
		this.init(defaults);
	}
	
	/**
	 * initializes the properties passed in and checks for flash.
	 */
	this.init = function(defaults)
	{
		for(var i in defaults)
		{
			this[i] = defaults[i];
		}
		
		try 
		{
			this.hasFlash = DetectFlashVer(this.flashMajorVersion, this.flashMinorVersion, this.flashRequiredRevision);
		}
		catch(e) 
		{
			this.hasFlash = false;
		}
	}
	
	/**
	 * Helper function to find the element by the id specified. Cross-browser compatible.
	 */
	this.getElementById = function(id)
	{
		var object = null;
		if (document.layers) {
			object = document.layers[id];
		} 
		else if (document.all) {
			object = document.all[id];
		}
		else if (document.getElementById) {
			object = document.getElementById(id);
		}
		return object;
	}
	
	/**
	 * Runs the actual detection and injects the proper video. 
	 */
	this.detect = function()
	{
		var result = '';
		var element;
		var isMobile = false;
		
		if(this.detectAndroid)
		{
			if(this.detectOS(this.osKeyAndroid))
			{
				this.debug('HTML5Video::detect() - detectAndroid');
				result = this.onAndroidCallback();
				isMobile = true;
			}
		}
		if(this.detectBlackberry)
		{
			if(this.detectOS(this.osKeyBlackberry))
			{
				this.debug('HTML5Video::detect() - detectBlackberry');
				result = this.onBlackberryCallback();
				isMobile = true;
			}
		}
		if(this.detectIOS)
		{
			if(this.detectOS(this.osKeyiOS))
			{
				this.debug('HTML5Video::detect() - detectIOS');
				result = this.oniOSCallback();
				isMobile = true;
			}
		}
		
		if(!isMobile)
		{
			this.debug('HTML5Video::detect() - default to desktop');
			result += this.onDesktopCallback();
		}
		
		if(this.outputElementId != '')
		{
			element = this.getElementById(this.outputElementId);
			if(element != null)
			{
				this.debug('HTML5Video::detect() - outputElementId found. injecting.');
				element.innerHTML = result;
			}
			else
			{
				this.debug('HTML5Video::detect() - outputElementId not found. injecting into body.');
				document.write(result);
			}
		}
		else
		{
			this.debug('HTML5Video::detect() - outputElementId not provided. injecting into body.');
			document.write(result);
		}
	}
	
	/**
	 * Callback for when android is found.
	 */
	this.onAndroidCallback = function()
	{
		this.debug('HTML5Video::onAndroidCallback()');
		var output = '';
		
		this.mobileVideoObject.params['onclick'] = "this.play();";
		this.mobileVideoObject.params['poster'] = this.androidPosterImage.src;
		this.mobileVideoObject.params['src'] = this.mobileH264.src;
		
		this.onVideoEndCallback = this.outputFallbackImage;
		output = this.outputVideo(this.mobileVideoObject, this.mobileH264, this.mobileAdditionalVideos, this.mobilePosterImage);
		
		var videos = document.getElementsByTagName('video') || [];
		for (var i = 0; i < videos.length; i++) {
			videos[i].addEventListener('click', function(videoNode) {
				return function() {
					videoNode.play();
				};
			}(videos[i]));
		}
		
		return output;
	}
	
	/**
	 * Callback for when blackberry is found.
	 */
	this.onBlackberryCallback = function()
	{
		this.debug('HTML5Video::onBlackberryCallback()');
		var output = '';
		
		this.mobileVideoObject.params['poster'] = this.mobilePosterImage.src;

		this.onVideoEndCallback = this.outputFallbackImage;
		output = this.outputVideo(this.mobileVideoObject, this.mobileH264, this.mobileAdditionalVideos, this.mobilePosterImage);
		
		return output;
	}
	
	/**
	 * Callback for when iphone/ipod/ipad/ios is found.
	 */
	this.oniOSCallback = function()
	{
		this.debug('HTML5Video::oniOSCallback()');
		var output = '';
		
		this.mobileVideoObject.params['poster'] = this.mobilePosterImage.src;

		this.onVideoEndCallback = this.outputFallbackImage;
		output = this.outputVideo(this.mobileVideoObject, this.mobileH264, this.mobileAdditionalVideos, this.mobilePosterImage);
		
		return output;
	}
	
	/**
	 * Default callback when none of the above is found. Serves as a desktop.
	 */
	this.onDesktopCallback = function()
	{
		this.debug('HTML5Video::onDesktopCallback()');
		var output = '';
		this.mobileVideoObject.params['poster'] = this.desktopPosterImage.src;
		
		if(this.hasFlash && this.useFlashFirst)
		{
			this.debug('HTML5Video::onDesktopCallback() - has flash');
			this.onFlashEndCallback = this.outputVideo;
			this.onVideoEndCallback = this.outputFallbackImage;
			output = this.outputFlash(this.desktopVideoObject, this.desktopH264, this.desktopAdditionalVideos, this.desktopPosterImage, this.desktopFlashObject);
		}
		else
		{
			this.debug('HTML5Video::onDesktopCallback() - no flash');
			this.onVideoEndCallback = this.outputFlash;
			this.onFlashEndCallback = this.outputFallbackImage;
			output = this.outputVideo(this.desktopVideoObject, this.desktopH264, this.desktopAdditionalVideos, this.desktopPosterImage, this.desktopFlashObject);
		}
		
		return output;
	}
	
	/**
	 * Debugging method that looks for firebug console, or optionally injects into a container on the page.
	 */
	this.debug = function(message)
	{
		if (typeof console == "undefined" || typeof console.log == "undefined") var console = { log: function() {} }; 
	
		if(this.debugging == true)
		{
			if(typeof console != "undefined")
			{
				console.log(message);
			}
			if(this.debuggingElementId)
			{
				var element = this.getElementById(this.debuggingElementId);
				if(element != null)
				{
					element.innerHTML += message + '<br />\n';
				}
				else
				{
					document.write(message + '<br />\n');
				}
			}
		}
	}
	
	/**
	 * Detects the OS based on the OS key passed in. Uses the userAgent of the browser.
	 */
	this.detectOS = function(device)
	{
		var uagent = navigator.userAgent.toLowerCase();
		this.debug('HTML5Video::detectOS() - ' + uagent);
		for(var i in device)
		{
			this.debug('HTML5Video::detectOS() - ' + device[i]);
			if (uagent.search(device[i]) > -1)
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Outputs the HTML5 video.
	 */
	this.outputVideo = function(srcVideoObject, primaryVideoAsset, additionalVideos, fallbackImage, flashObject)
	{
		this.debug('HTML5Video::outputVideo()');
		var outputText = '';
		
		outputText += '<video width="' + srcVideoObject.width + '" height="' + srcVideoObject.height + '" ';
		
		for(var i in srcVideoObject.params)
		{
			outputText += ' ' + i + '="' + srcVideoObject.params[i] + '"';
		}
		
		outputText += '>';
		
		outputText += '<source src="' + primaryVideoAsset.src + '" type="' + primaryVideoAsset.type + '" />';
		
		for(var n in additionalVideos)
		{
			outputText += '<source src="' + additionalVideos[n].src + '" type="' + additionalVideos[n].type + '" />';
		}
		
		outputText += this.onVideoEndCallback(srcVideoObject, primaryVideoAsset, additionalVideos, fallbackImage, flashObject)
		
		outputText += '</video>';
		
		return outputText;
	}
	
	/**
	 * Call for adding additional code to inject into video element. example: params and fallback image
	 * @return string
	 */
	this.onVideoEndCallback = this.outputFallbackImage;
	
	/**
	 * Outputs the flash html and calls the callback <code>onFlashEndCallback</code> before closing the flash tag.
	 * @return string
	 */
	this.outputFlash = function(srcVideoObject, primaryVideoAsset, additionalVideos, fallbackImage, flashObject)
	{
		this.debug('HTML5Video::outputFlash()');
		var outputText = '';
		
		outputText += '<object width="' + flashObject.width + '" height="' + flashObject.height + '" type="application/x-shockwave-flash" data="' + flashObject.src + '">';
		
		if(flashObject.params['movie'] == null)
		{
			outputText += '<param name="movie" value="' + flashObject.src + '" />';
		}
		
		for(var i in flashObject.params)
		{
			outputText += '<param name="' + i + '" value="' + flashObject.params[i] + '" />';
		}
		
		outputText += this.onFlashEndCallback(srcVideoObject, primaryVideoAsset, additionalVideos, fallbackImage, flashObject)
		
		outputText += '</object>';
		
		return outputText;
	}
	
	/**
	 * Call for adding additional code to inject into flash object. example: params and fallback image
	 * @return string
	 */
	this.onFlashEndCallback = this.outputFallbackImage;

	/**
	 * Outputs the fallback image html.
	 * @return string
	 */
	this.outputFallbackImage = function(srcVideoObject, primaryVideoAsset, additionalVideos, fallbackImage, flashObject)
	{
		this.debug('HTML5Video::outputFallbackImage()');
		
		var outputText = '<img src="' + fallbackImage.src + '" width="' + fallbackImage.width + '" height="' + fallbackImage.height + '" title="' + fallbackImage.title + '" alt="' + fallbackImage.alt + '" />';
		return outputText;
	}
	
}

/**
 * HTML5VideoObject. Specifies the attributes for the video tag.
 * @param width
 * @param height
 * @param params This is the optional params for the video tag. Ex: {autoplay:"autoplay", controls:"controls", height:"100px", width:"100px", loop:"loop", preload:"preload", src:"sample_url.mp4"
 * @return
 */
function HTML5VideoObject(width, height, params)
{
	this.width = width;
	this.height = height;
	this.params = params;
}

function HTML5VideoAsset(src, type)
{
	this.src = src;
	this.type = type;
}

function HTML5VideoImage(src, width, height, alt, title)
{
	this.src = src;
	this.width = width;
	this.height = height;
	this.alt = alt;
	this.title = title;
}

function HTML5VideoFlashObject(src, width, height, params)
{
	this.src = src;
	this.width = width;
	this.height = height;
	this.params = params;
}
