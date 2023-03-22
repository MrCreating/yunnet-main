(function (unt) {
    unt.modules.profile = {
        init: function () {
            try {
                document.title = unt.users.lang.profile;

                if (document.getElementsByClassName('not_found')[0])
                    throw new Error('Not found');

                let user_id = Number(document.getElementById('current_user_id').getAttribute('data-id')) || 0;

                let loader = unt.loader().build();
                loader.style.padding = '20px';

                document.getElementsByClassName('loader')[0].appendChild(loader);

                let posts_div = document.getElementsByClassName('posts_list')[0];

                unt.users.get(user_id).then(function (user) {
                    document.title += ' - ' + (user.name || (user.first_name + ' ' + user.last_name));

                    unt.modules.profile.loadPosts(user_id, 1).then(function (posts) {
                        posts.forEach(function (post) {
                            posts_div.appendChild(unt.components.post(post));
                        });

                        loader.style.display = 'none';
                        return posts_div.style.display = '';
                    });
                }).catch(function (err) {
                    loader.style.display = 'none';
                    document.getElementById('error_message').style.display = '';
                })

                unt.changeWindowTo(1);
            } catch (e) {
                document.title = unt.users.lang.not_found;
                unt.changeWindowTo(0);
            }
        },
        logout: function () {
            unt.request.post('/settings', {
                action: 'logout'
            }).then(function () {
                return window.location.reload();
            }).catch(function () {
                return unt.toast({html: unt.users.lang.load_error});
            });
        },
        loadPosts: function (user_id, page = 1) {
            return unt.request.post((user_id === 0 ? "/" : (user_id > 0 ? "/id" + user_id : "/bot" + user_id * -1)), {
                offset: (page - 1) * 30,
                count: 30,
                action: 'get_posts'
            }).then(function (response) {
                return JSON.parse(response);
            })
        }
    };
})(unt);