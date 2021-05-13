let ActivSelectDay = moment();
let DataTableLanguageRU={
    "processing": "Подождите...",
    "search": "Поиск:",
    "lengthMenu": "Показать _MENU_ записей",
    "info": "Записи с _START_ до _END_ из _TOTAL_ записей",
    "infoEmpty": "Записи с 0 до 0 из 0 записей",
    "infoFiltered": "(отфильтровано из _MAX_ записей)",
    "loadingRecords": "Загрузка записей...",
    "zeroRecords": "Записи отсутствуют.",
    "emptyTable": "В таблице отсутствуют данные",
    "paginate": {
        "first": "Первая",
        "previous": "Предыдущая",
        "next": "Следующая",
        "last": "Последняя"
    },
    "aria": {
        "sortAscending": ": активировать для сортировки столбца по возрастанию",
        "sortDescending": ": активировать для сортировки столбца по убыванию"
    },
    "select": {
        "rows": {
            "_": "Выбрано записей: %d",
            "0": "Кликните по записи для выбора",
            "1": "Выбрана одна запись"
        }
    }
};

function GetSelectData() {
    try {
        if ($('#txtDataRange').val().length != 21)
            throw new Error();
        var dtsplit = $('#txtDataRange').val().split("-", 2);
        if (dtsplit.length != 2)
            throw new Error();
    } catch (err) {
        console.log("Ошибка");
        return;
    }
    return {
        Start: moment(dtsplit[0], 'DD.MM.YYYY').unix(),
        End: moment(dtsplit[1] + " 23:59:59", 'DD.MM.YYYY HH:mm:ss').unix()
    }
}