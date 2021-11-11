unt.actions.linkWorker.go = function (url = window.location.href, writeToLocalHistory = true, internalData = null) {
	let splittedUrl = String(url).split(window.location.host)
	let resultedUrl = (splittedUrl[1] ? splittedUrl[1] : (splittedUrl[0] ? splittedUrl[0] : '/'));

	if (!resultedUrl.startsWith('/'))
		return (window.location.href = url);

	unt.components.menuElement ? unt.components.menuElement.innerHTML = '' : '';
	if (writeToLocalHistory) {
		let pageInfoObject = {id: this.history.length, url: url};
		this.history.push(pageInfoObject);
		history.pushState(pageInfoObject, document.title, resultedUrl);

		this.currentPage = pageInfoObject;
	}

	let resultedUrlWithOutParams = resultedUrl.split('?')[0];
	switch (resultedUrlWithOutParams) {
		case '/':
			unt.settings.users.current ? unt.pages.main(internalData) : unt.pages.auth(internalData);
		break;
	}
}

window.addEventListener('DOMContentLoaded', function (event) {
	if (window.opener) {
		/*window.addEventListener('beforeunload', function () {
			if (!window.isInRequest)
				return window.opener.postMessage({status: -1, withOutTimeout: true}, '*');
		});*/
	}

	let spinner = document.getElementById('load_indicator');
	if (spinner)
		spinner.style.display = '';

	let loader = document.getElementById('load');
	unt.actions.currentMobile = unt.tools.isMobile();

	return unt.components.initDefaultForm().then(function () {
		spinner.style.display = 'none';

		let menuBody = document.createElement('div');
		document.body.appendChild(menuBody);

		unt.components.menuElement = menuBody;

		unt.actions.linkWorker.go(window.location.href);
		return setTimeout(function () {
			unt.AutoInit();

			if (loader)
				return loader.remove();
		}, 500);
	});
});

window.addEventListener('resize', function () {
	if (unt.tools.isMobile() && !unt.actions.currentMobile) {
		if (document.body.redirecting) return;

		document.body.innerHTML = '';
		document.body.redirecting = true;

		setTimeout(function () {
			unt.toast({html: 'Redirecting to mobile...'});

			return setTimeout(function () {
				return window.location.reload();
			}, 1000);
		}, 500);
	}
	if (!unt.tools.isMobile() && unt.actions.currentMobile) {
		return window.location.reload();
	}

	unt.AutoInit();
})

window.addEventListener('popstate', function handleBackPressed (event) {
	event.preventDefault();
	
	if (event.state) {
		unt.actions.linkWorker.currentPage = event.state;
		unt.actions.linkWorker.go(event.state.url, false);
	}
})