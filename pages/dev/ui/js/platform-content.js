unt.pages = new Object({
	auth: function (internalData) {},
	registrer: function (internalData) {},
	restore: function (internalData) {},
	banned: function (internalData) {},
	news: function (internalData) {
		document.title = unt.settings.lang.getValue('news');
	},
	messages: function (internalData) {
		document.title = unt.settings.lang.getValue('messages');
	},
	notifications: function (internalData) {
		document.title = unt.settings.lang.getValue('notifications');
	},
	friends: function (internalData) {
		document.title = unt.settings.lang.getValue('friends');
	},
	groups: function (internalData) {
		document.title = unt.settings.lang.getValue('groups');
	},
	group: function (internalData) {},
	archive: function (internalData) {
		document.title = unt.settings.lang.getValue('archive');
	},
	audios: function (internalData) {
		document.title = unt.settings.lang.getValue('audios');
	},
	settings: function (internalData) {
		document.title = unt.settings.lang.getValue('settings');
	},
	edit: function (internalData) {
		document.title = unt.settings.lang.getValue('edit');
	},
	profile: function (internalData) {
		document.title = unt.settings.lang.getValue('profile');
	},
	about: function (internalData) {
		document.title = unt.settings.lang.getValue('about');
	},
	wall: function (internalData) {
		document.title = unt.settings.lang.getValue('wall');
	},
	photo: function (internalData) {
		document.title = unt.settings.lang.getValue('photo');
	}
});