unt.groups = {
    get: function (offset, count) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'get_list');
            data.append('offset', Number(offset).toString());
            data.append('count', Number(count).toString());

            return ui.Request({
                url: '/groups',
                method: 'POST',
                data: data,
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    try {
                        response = JSON.parse(response);

                        return resolve(response);
                    } catch (e) {
                        return reject();
                    }
                },
                error: reject
            });
        });
    },
    create: function (title, description) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'create_group');
            data.append('title', title.trim());
            data.append('description', description.trim());

            return ui.Request({
                url: '/groups',
                method: 'POST',
                data: data,
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    try {
                        response = JSON.parse(response);

                        return resolve(response);
                    } catch (e) {
                        return reject();
                    }
                },
                error: reject
            });
        });
    },
    group: function (object) {
        let element = document.createElement('div');
        element.addEventListener('click', function (event) {
            return ui.go('/group' + object.id, true);
        });

        element.classList = ['collection-item card waves-effect'];
        element.style.margin = '2px';
        element.style.height = '90px';
        element.style.marginLeft = element.style.marginRight = 0;
        element.style.width = '100%';
        element.style.padding = '20px';

        let groupInfoContainer = document.createElement('div');
        groupInfoContainer.classList.add('valign-wrapper');
        groupInfoContainer.style.height = '100%';
        element.appendChild(groupInfoContainer);

        let groupPhoto = document.createElement('img');
        groupPhoto.style.marginRight = '15px';
        groupInfoContainer.appendChild(groupPhoto);
        groupPhoto.classList.add('circle');
        groupPhoto.width = groupPhoto.height = 48;
        groupPhoto.src = 'https://dev.yunnet.ru/images/default.png';

        let groupNameDiv = document.createElement('div');
        groupNameDiv.style.height = '100%';
        groupInfoContainer.appendChild(groupNameDiv);

        let groupTitle = document.createElement('div');
        groupNameDiv.appendChild(groupTitle);
        groupTitle.style.height = '25px';
        groupTitle.style.overflow = 'hidden';
        groupTitle.style.textOverflow = 'ellipsis';

        let titleB = document.createElement('b');
        groupTitle.appendChild(titleB);
        titleB.innerText = object.title;

        let statusTextDiv = document.createElement('div');
        statusTextDiv.innerText = object.status;
        groupNameDiv.appendChild(statusTextDiv);
        statusTextDiv.style.height = '25px';
        statusTextDiv.style.overflow = 'hidden';
        statusTextDiv.style.textOverflow = 'ellipsis';

        return element;
    }
};