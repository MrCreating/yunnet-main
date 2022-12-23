function create_new_group (process = false) {
    if (!process) {
        let winDiv = document.getElementById('add_group');
        let instance = null;
        if (!winDiv) {
            winDiv = document.createElement('div');

            winDiv.innerHTML = `
        <div id="add_group" class="add_group modal">
            <div class="modal-content">
                <div class="valign-wrapper" style="width: 100%">
                    <h5 style="width: 100%">Добавить новую группу</h5>
                    <i onclick="M.Modal.getInstance(document.getElementById('add_group')).close();" class="material-icons" style="margin-top: 12px; cursor: pointer">close</i>
                </div>
                <div>
                    <div class="input-field">
                        <input id="group_name" type="text" class="validate">
                        <label for="group_name">Название группы</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="edit_save" class="waves-effect waves-teal btn-flat" onclick="create_new_group(true)">Создать</a>
            </div>
        </div>`;

            document.body.appendChild(winDiv);

            instance = M.Modal.init(winDiv.getElementsByClassName('add_group')[0], {
                onCloseEnd: function () {
                    winDiv.remove();
                    document.body.style.overflowX = 'hidden';
                },
                onCloseStart: function () {
                    document.body.style.overflowX = 'hidden';
                }
            });
        } else {
            instance = M.Modal.getInstance(winDiv);
        }

        instance.open();
    } else {
        let btn = document.getElementById('edit_save');

        btn.innerText = 'Подождите...'
        btn.setAttribute('disabled', 'true');

        $.ajax({
            url: '/flex',
            type: 'POST',
            data: {
                action: 'create_group',
                group_name: document.getElementById('group_name').value
            },
            success: function (response) {
                try {
                    response = JSON.parse(response);

                    if (response.success) {
                        M.toast({html: 'Группа создана.'});
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    }

                    if (response.error)
                        throw new Error();
                } catch (e) {
                    btn.innerText = 'Создать';
                    btn.removeAttribute('disabled');
                    M.toast({html: 'Ошибка при создании группы. Попробуйте снова.'});
                }
            },
            error: function (error) {
                btn.innerText = 'Создать';
                btn.removeAttribute('disabled');
                M.toast({html: 'Ошибка при создании группы. Попробуйте снова.'});
            }
        });
    }
}

function delete_group (process_in = false, group_id, group_name) {
    if (!process_in) {
        let win_div = document.getElementById('delete_group');
        let instance = null;
        if (!win_div) {
            win_div = document.createElement('div');

            win_div.innerHTML = `
        <div id="delete_group" class="delete_group modal">
            <div class="modal-content">
                <div class="valign-wrapper" style="width: 100%">
                    <h5 style="width: 100%">Удаление группы</h5>
                    <i onclick="M.Modal.getInstance(document.getElementById('delete_grou')).close();" class="material-icons" style="margin-top: 12px; cursor: pointer">close</i>
                </div>
                <div>
                    Вы действительно хотите удалить группу: ${group_name}? Это действие невозможно отменить!
                </div>
            </div>
            <div class="modal-footer">
                <a id="delete_no" class="waves-effect waves-teal btn-flat" onclick="M.Modal.getInstance(document.getElementById('delete_group')).close();">Нет</a> 
                <a id="delete_yes" class="waves-effect waves-teal btn-flat" onclick="delete_group(true, ${group_id}, '${group_name}')">Да</a>
            </div>
        </div>`;

            document.body.appendChild(win_div);

            instance = M.Modal.init(win_div.getElementsByClassName('delete_group')[0], {
                onCloseEnd: function () {
                    win_div.remove();
                    document.body.style.overflowX = 'hidden';
                },
                onCloseStart: function () {
                    document.body.style.overflowX = 'hidden';
                }
            });
        } else {
            instance = M.Modal.getInstance(win_div);
        }

        instance.open();
    } else {
        let btn2 = document.getElementById('delete_yes');

        btn2.innerText = 'Удаляем...';
        btn2.setAttribute('disabled', 'true');

        $.ajax({
            url: '/flex',
            type: 'POST',
            data: {
                action: 'delete_group',
                group_id: group_id,
            },
            success: function (response) {
                try {
                    response = JSON.parse(response);

                    if (response.success) {
                        M.toast({html: 'Группа удалена.'});
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    }

                    if (response.error)
                        throw new Error();
                } catch (e) {
                    btn2.innerText = 'Да';
                    btn2.removeAttribute('disabled');
                    M.toast({html: 'Ошибка при удалении группы. Попробуйте снова.'});
                }
            },
            error: function (error) {
                btn2.innerText = 'Создать';
                btn2.removeAttribute('disabled');
                M.toast({html: 'Ошибка при удалении группы. Попробуйте снова.'});
            }
        });
    }
}