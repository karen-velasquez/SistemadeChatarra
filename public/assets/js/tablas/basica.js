
    // Setup - add a text input to each footer cell
    $('#datos').DataTable({
        "language": {
            "processing": "Procesando...",
            "lengthMenu": 'Filtrar <select>'+
                '<option value="10">10</option>'+
                '<option value="20">20</option>'+
                '<option value="30">30</option>'+
                '<option value="40">40</option>'+
                '<option value="50">50</option>'+
                '<option value="-1">Todos</option>'+
                '</select> Registros',
            "paginate": {
                "sFirst":    "Primero",
                "sLast":    "Último"
            },
            "info": "Pagina _PAGE_ de _PAGES_",
            "search": "Buscador Gral",
            "emptyTable": "No existen datos registrados.",
            "infoEmpty": "",
        },
        orderCellsTop: true,
        fixedHeader: true,
        ordering: false,
        pageLength: 20
    });
    
