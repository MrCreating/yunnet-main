$(document).ready(function () {
    let datasource = JSON.parse($('#table-data').attr('data-items'));

    let result = [];

    datasource.forEach(function (student, index) {
        let date = new Date();

        result.push({
            index: index,
            name: student.first_name + ' ' + student.last_name.substring(0, 1) + '.',
            student_id: student.student_id,
            date: date.getDay() + '.' + date.getMonth() + '.' + date.getFullYear()
        })
    })

    PUSH('datasource', result);
    COMPILE();
});

//добавить запись
function addRow(e) {
    if (!VALIDATE('form.*')) return;
    PUSH('datasource', form);
    SET('form', null);
    RESET('form.*');
}

//удалить запись
function remRow(e) {
    var tr = $(e).closest('tr');
    var ind = tr.data('index');
    datasource.splice(ind, 1);
    UPDATE('datasource');
}