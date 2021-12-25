unt.modules.uploads = {
	uploader: function () {
		return new Promise(function (resolve, reject) {
			let win = unt.components.windows.createImportantWindow({
				cloaseAble: true,
				onClose: function () {
					return resolve(null);
				},
				title: unt.settings.lang.getValue('select_a_file')
			});

			let menu = win.getMenu();

			let tabsUl = document.createElement('ul');
			menu.appendChild(tabsUl);

			win.show();
			unt.Tabs.init();
		});
	},
	upload: function (file) {
		return new Promise(function () {

		});
	}
};