unt.modules = {};
unt.actions.linkWorker.go = function (url = window.location.href, writeToLocalHistory = true, internalData = null) {
	let splittedUrl = String(url).split(window.location.host)
	let resultedUrl = (splittedUrl[1] ? splittedUrl[1] : (splittedUrl[0] ? splittedUrl[0] : '/'));

	if (!resultedUrl.startsWith('/'))
		return (window.location.href = url);

	unt.components.menuElement ? unt.components.menuElement.innerHTML = '' : '';
	unt.actions.wall.currentId = null;
	if (writeToLocalHistory) {
		let pageInfoObject = {id: this.history.length, url: url};
		this.history.push(pageInfoObject);
		history.pushState(pageInfoObject, document.title, resultedUrl);

		this.currentPage = pageInfoObject;
	}

	let resultedUrlWithOutParams = resultedUrl.split('?')[0];
	switch (resultedUrlWithOutParams) {
		case '/':
			unt.settings.users.current ? unt.pages.news(internalData) : unt.pages.auth(internalData);
		break;
		case '/login':
			unt.settings.users.current ? unt.pages.news(internalData) : unt.pages.auth(internalData);
		break;
		case '/register':
			unt.settings.users.current ? unt.pages.news(internalData) : unt.pages.register(internalData);
		break;
		case '/restore':
			unt.settings.users.current ? unt.pages.news(internalData) : unt.pages.restore(internalData);
		break;
		case '/messages':
			unt.settings.users.current ? unt.pages.messages(internalData) : unt.pages.auth(internalData);
		break;
		case '/notifications':
			unt.settings.users.current ? unt.pages.notifications(internalData) : unt.pages.auth(internalData);
		break;
		case '/friends':
			unt.settings.users.current ? unt.pages.friends(internalData) : unt.pages.auth(internalData);
		break;
		case '/groups':
			unt.settings.users.current ? unt.pages.groups(internalData) : unt.pages.auth(internalData);
		break;
		case '/archive':
			unt.settings.users.current ? unt.pages.archive(internalData) : unt.pages.auth(internalData);
		break;
		case '/audios':
			unt.settings.users.current ? unt.pages.audios(internalData) : unt.pages.auth(internalData);
		break;
		case '/settings':
			unt.settings.users.current ? unt.pages.settings(internalData) : unt.pages.auth(internalData);
		break;
		case '/edit':
			unt.settings.users.current ? unt.pages.edit(internalData) : unt.pages.auth(internalData);
		break;
		case '/about':
			unt.settings.users.current ? unt.pages.about(internalData) : unt.pages.auth(internalData);
		break;
		default:
			if (resultedUrl.startsWith('/wall'))
				unt.pages.wall(internalData)
			else if (resultedUrl.startsWith('/photo'))
				unt.pages.photo(internalData)
			else
				unt.pages.profile(internalData)
		break;
	}

	return this.define();
}

window.addEventListener('DOMContentLoaded', function (event) {
	let spinner = document.getElementById('load_indicator');
	if (spinner)
		spinner.style.display = '';

	let loader = document.getElementById('load');
	unt.actions.currentMobile = unt.tools.isMobile();

	return unt.components.initDefaultForm().then(function () {
		spinner.style.display = 'none';

		let menuBody = unt.components.buildDefaultPageForm();
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