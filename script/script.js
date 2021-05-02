$(document).ready(function () {
    $("#bodyContent").hide().load("components/divAntipastiContorni.html", function () {
        var getProduct = ["antipasto", "contorno"];
        printProduct(getProduct);
        getNumberProductInCart();
        var height = $(window).height();
        $("#menuHeader").css("height", height / 100 * 10 + "px");
        $("#menuFooter").css("height", height / 100 * 10 + "px");
        $("#bodyContent").css("height", height / 100 * 80 + "px");
        $("#tavoli").css("height", height + "px");
        var gestureHandler = new Hammer(document.getElementById("menuHeader"));
        var gestureHandlerBottomTavoli = new Hammer(document.getElementById("menuFooterTavoli"));
        var gestureHandlerBottom = new Hammer(document.getElementById("menuFooter"));
        var gestureHandlerTopCassa = new Hammer(document.getElementById("menuHeaderCassa"));

        gestureHandlerTopCassa.get('swipe').set({direction: Hammer.DIRECTION_VERTICAL});
        gestureHandlerBottom.get('swipe').set({direction: Hammer.DIRECTION_VERTICAL});
        gestureHandler.get('swipe').set({direction: Hammer.DIRECTION_VERTICAL});
        gestureHandlerBottomTavoli.get('swipe').set({direction: Hammer.DIRECTION_VERTICAL});
        gestureHandler.on("swipedown", function () {
            getTavoliOccupati();
            getOrdiniAsporto();
            $("#menuHeader, #menuFooter, #bodyContent").fadeOut('500');
            $("#tavoli").fadeIn('500');
        });
        gestureHandlerBottom.on("swipeup", function () {
            $("#menuHeader, #menuFooter, #bodyContent").fadeOut('500');
            $("#cassa").fadeIn('500')
        });
        gestureHandlerBottomTavoli.on("swipeup", function () {
            $("#menuHeader, #menuFooter, #bodyContent").fadeIn('500');
            $("#tavoli").fadeOut('500');
        });
        gestureHandlerTopCassa.on("swipedown", function () {
            $("#menuHeader, #menuFooter, #bodyContent").fadeIn('500');
            $("#cassa").fadeOut('500');
        });

    }).fadeIn('500');

    $("input[name='payment']").change(function() {
        document.methodPayment = $(this).val();
    })
});

function openDrawer() {
    $.ajax({
        url: "controller/CassaController.php",
        method: "POST",
        data: {
            method: 'openDrawer'
        },
        success: function (response) {

        }
    });
}

function chiusuraFiscale() {
    if (confirm("Sicuro di voler fare la chiusura fiscale?")) {
        $.ajax({
            url: "controller/CassaController.php",
            method: "POST",
            data: {
                method: 'chiusuraFiscale'
            },
            success: function (response) {
                location.reload();
            }
        });
    }
}

function previewBill(tavolo, cognome) {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'previewBill',
            tavolo: tavolo,
            cognome: cognome
        },
        success: function (response) {
            var res = JSON.parse(response);
            $("#tableHeaderTavoli").hide().html("<tr><td>Tavolo/Cliente " + res.carrello.identificativo + "</td></tr>").fadeIn('500');
            var costoTotale = parseFloat(res.carrello.totale).toFixed(2);
            if (costoTotale == 0) {
                if (tavolo != null) {
                    deleteTable(tavolo);
                } else {
                    deleteAsporto(cognome);
                }
                location.reload();
            }
            $("#bodyContentTavoli").hide().load('components/divRiepilogoOrdine.html', function () {
                $(res.carrello.prodotti).each(function () {
                    console.log(this);
                    var nomeCat = this.prodotto.categoria;
                    var nomeProd = this.prodotto.nome;
                    var qntProd = this.quantita;
                    var totParziale = (this.quantita * this.prodotto.prezzo).toFixed(2);

                    if (nomeCat == "primo_di_pesce" || nomeCat == "primo_di_carne") {
                        nomeCat = "primi";
                    }
                    if (nomeCat == "secondo_di_pesce" || nomeCat == "secondo_di_carne") {
                        nomeCat = "secondi";
                    }
                    $("#div" + nomeCat).fadeIn('500');
                    if (parseInt(res.pax) == 0)
                        $("#table" + nomeCat).append("<tr><td style=\"width: 70%;\">" + qntProd + " x " + nomeProd + "</td><td style='width: 10%;'>" + totParziale + "€</td><td onclick=\"deleteFromOrder('" + nomeProd + "', null, '" + res.carrello.identificativo + "');\"><i class=\"material-icons\" style=\"color: red;\">remove</i></td></tr>");
                    else
                        $("#table" + nomeCat).append("<tr><td style=\"width: 70%;\">" + qntProd + " x " + nomeProd + "</td><td style='width: 10%;'>" + totParziale + "€</td><td onclick=\"deleteFromOrder('" + nomeProd + "', '" + res.carrello.identificativo + "', null);\"><i class=\"material-icons\" style=\"color: red;\">remove</i></td></tr>");
                })
            }).fadeIn('500');

            if (parseInt(res.pax) != 0)
                $("#menuFooterTavoli table").hide().html("<tr><td style='width: 15%;' onclick='location.reload();'><i class='material-icons'>arrow_back</i></td><td>Totale incl. coperto " + costoTotale + "€</td><td style='width: 10%;' onclick=\"makeFakeBill('" + res.carrello.identificativo + "');\">pr</td><td style='width: 15%;' onclick=\"makeBill('" + res.carrello.identificativo + "');\"><i class='material-icons'>done_all</i></tr>").fadeIn('500');
            else
                $("#menuFooterTavoli table").hide().html("<tr><td style='width: 15%;' onclick='location.reload();'><i class='material-icons'>arrow_back</i></td><td>Totale incl. coperto " + costoTotale + "€</td><td style='width: 15%;' onclick=\"makeBillAsporto('" + res.carrello.identificativo + "');\"><i class='material-icons'>done_all</i></tr>").fadeIn('500');

        }
    });
}

function deleteFromOrder(prodotto, tavolo, cognome) {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'decreaseProductFromOrder',
            prodotto: prodotto,
            tavolo: tavolo,
            cognome: cognome
        },
        success: function (response) {
            previewBill(tavolo, cognome);
        }
    });
}

function makeBill(tavolo) {
    $("#btnScontrino").attr("tavolo", tavolo).attr("cognome", "");
    $("#modalPagamento").modal('toggle');
}

function makeFakeBill(tavolo) {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'makeFakeBill',
            tavolo: tavolo
        },
        success: function (response) {
            if (response == "ok") {
                location.reload();
            } else {
                alert("Qualcosa non va, controlla il collegamento");
            }
        }
    });
}

function makeBillAsporto(cognome) {
    $("#btnScontrino").attr("tavolo", "").attr("cognome", cognome);
    $("#modalPagamento").modal('toggle');
}

function makeBillReal() {
    let cognome = $("#btnScontrino").attr("cognome");
    let tavolo = $("#btnScontrino").attr("tavolo");
    let method = document.methodPayment;
    if (cognome != "") {
        $.ajax({
            url: "controller/CarrelloController.php",
            method: "POST",
            data: {
                method: 'makeBillAsporto',
                cognome: cognome,
                payment: method
            },
            success: function (response) {
                if (response == "ok") {
                    location.reload();
                } else {
                    alert("Qualcosa non va, controlla il collegamento");
                }
            }
        });
    } else {
        if (confirm("Sicuro di voler stampare lo scontrino del tavolo " + tavolo + " ?")) {
            $.ajax({
                url: "controller/CarrelloController.php",
                method: "POST",
                data: {
                    method: 'makeBill',
                    tavolo: tavolo,
                    payment: method
                },
                success: function (response) {
                    if (response == "ok") {
                        location.reload();
                    } else {
                        alert("Qualcosa non va, controlla il collegamento");
                    }
                }
            });
        }
    }
}
 function deleteAsporto(cognome) {
    if (confirm("Sicuro di voler cancellare l'ordine di " + cognome + " ?")) {
        $.ajax({
            url: "controller/CarrelloController.php",
            method: "POST",
            data: {
                method: 'deleteAsportoOrder',
                cognome: cognome
            },
            success: function (response) {
                getOrdiniAsporto();
            }
        });
    }
}

function getOrdiniAsporto() {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'retriveAllAsportoOrder'
        },
        success: function (response) {
            var res = JSON.parse(response);
            $("#tableasporto").html("");
            $(res).each(function () {
                $("#tableasporto").append("<tr><td>" + this.cognome + "</td><td style=\"width: 10%;\" onclick=\"deleteAsporto('" + this.cognome + "')\"><i class=\"material-icons\">close</i></td><td onclick=\"previewBill(null, '" + this.cognome + "');\" style=\"width: 10%;\"><i class=\"material-icons\">payment</i></td></tr>");
            })
        }
    });
}

function getTavoliOccupati() {
    $.ajax({
        url: "controller/TavoloController.php",
        method: "POST",
        data: {
            method: 'getBusyTable'
        },
        success: function (response) {
            var res = JSON.parse(response);
            $("#tabletavoli").html("");
            $(res).each(function () {
                $("#tabletavoli").append("<tr><td>NÂ° " + this.numero + " - " + this.coperti + " PAX</td><td style=\"width: 10%;\" onclick=\"deleteTable('" + this.numero + "')\"><i class=\"material-icons\">close</i></td><td onclick=\"previewBill('" + this.numero + "', null);\" style=\"width: 10%;\"><i class=\"material-icons\">payment</i></td><td style='width: 10%;' onclick=\"addProductToTable('" + this.numero + "');\"><i class='material-icons'>shopping_cart</i></td></tr>");
            })
        }
    });
}

function addProductToTable(tableNumber) {
    $.ajax({
        url: "controller/TavoloController.php",
        method: "POST",
        data: {
            method: 'setSessionTable',
            tavolo: tableNumber
        },
        success: function (response) {
            $("#menuHeader, #menuFooter, #bodyContent").fadeIn('500');
            $("#tavoli").fadeOut('500');
        }
    });
}

function deleteTable(tavolo) {
    if (confirm("Sicuro di voler cancellare il tavolo " + tavolo + " ?")) {
        $.ajax({
            url: "controller/TavoloController.php",
            method: "POST",
            data: {
                method: 'deleteAllTableContent',
                tavolo: tavolo
            },
            success: function (response) {
                getTavoliOccupati();
            }
        });
    }
}

function exitFromTable() {
    $.ajax({
        url: "controller/TavoloController.php",
        method: "POST",
        data: {
            method: 'removeSessionTable'
        },
        success: function (response) {
            orderResume();
        }
    });
}

function getNumberProductInCart() {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'numberProductInCart'
        },
        success: function (response) {
            var res = JSON.parse(response);
            var numProd = parseInt(res.numeroProdotti);
            if (numProd == 0) {
                $("#tableInfoCarrello").hide().html("<tr><td>Non ci sono prodotti nell'ordine</td></tr>").fadeIn('500');

            } else {
                $("#tableInfoCarrello").hide().html("<tr><td onclick='newOrder()' style='width: 15%;'><i class='material-icons'>close</i></td><td>Nell'ordine ci sono " + numProd + " prodotti</td><td style='width: 15%;' onclick='orderResume()'><i class='material-icons'>send</i></td></tr>").fadeIn('500');

            }
        }
    });
}

function orderResume() {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'orderResume'
        },
        success: function (response) {
            var result = JSON.parse(response);
            if (result.hasOwnProperty('tavolo')) {
                $("#tableHeader").hide().html('<tr><td style=\"width: 85%;\">Aggiunta all\'ordine del tavolo ' + result.tavolo + '</td><td onclick=\"exitFromTable();\"><i class=\"material-icons\">close</i></td></tr>').fadeIn('500');
            } else {
                $("#tableHeader").hide().html('<tr><td>Riepilogo ordine</td></tr>').fadeIn('500');
            }
            var res = result.carrello;
            var costoTotale = res.totale;
            $("#bodyContent").hide().load('components/divRiepilogoOrdine.html', function () {
                $(res.prodotti).each(function () {
                    var nomeCat = "antipasto";
                    var nomeProd = this.prodotto.nome;
                    if (nomeProd == "barra") {
                        $("#table" + nomeCat).append("<tr><td colspan = '3' style='text-align: center;'> ------------- </td><td onclick=\"decreaseFromCart('barra')\"><i class='material-icons' style='color: red;'>remove</i></td></tr>");
                    } else {
                        var qntProd = this.quantita;
                        $("#div" + nomeCat).fadeIn('500');
                        $("#table" + nomeCat).append("<tr><td onclick=\"addBarra();\" style=\"width: 70%;\">" + qntProd + " x " + nomeProd + "</td><td onclick=\"showNote('" + nomeProd + "');\"><i class=\"material-icons\">note_add</i></td><td onclick=\"addToCart('" + nomeProd + "', 'carrello');\"><i class=\"material-icons\" style=\"color: green;\">add</i></td><td onclick=\"decreaseFromCart('" + nomeProd + "');\"><i class=\"material-icons\" style=\"color: red;\">remove</i></td></tr>");
                    }
                });
            }).fadeIn('500');

            if (result.hasOwnProperty('tavolo')) {
                $("#tableInfoCarrello").hide().html("<tr><td onclick=\"location.reload();\"><i class=\"material-icons\">keyboard_backspace</i></td><td style=\"width: 70%;\">Totale: " + costoTotale + " €</td><td onclick='confirmOrder(true);'><i class=\"material-icons\">done_all</i></td></tr>").fadeIn('500');
            } else {
                $("#tableInfoCarrello").hide().html("<tr><td onclick=\"location.reload();\"><i class=\"material-icons\">keyboard_backspace</i></td><td style=\"width: 70%;\">Totale: " + costoTotale + " €</td><td onclick='continueToOrder();'><i class=\"material-icons\">check</i></td></tr>").fadeIn('500');
            }
        }
    });
}

function continueToOrder() {
    $("#bodyContent").hide().load('components/divConfermaOrdine.html', function () {
        $.ajax({
            url: "controller/TavoloController.php",
            method: "POST",
            data: {
                method: 'getFreeTable'
            },
            success: function (response) {
                var res = JSON.parse(response);
                var costoTotale = res.totaleCarrello;
                $(res.tavoliLiberi).each(function () {
                    $("#numeroTavolo").append("<option value='" + this.numero + "'>" + this.numero + "</option>");
                });
                $("#tableHeader").hide().html('<tr><td>Conferma ordine</td></tr>').fadeIn('500');
                $("#tableInfoCarrello").hide().html("<tr><td onclick=\"orderResume();\"><i class=\"material-icons\">keyboard_backspace</i></td><td style=\"width: 70%;\">Totale: " + costoTotale + " â‚¬</td><td onclick='confirmOrder(false);'><i class=\"material-icons\">done_all</i></td></tr>").fadeIn('500');
                $("#asporto").change(function () {
                    if (this.checked) {
                        $("#rowNumeroTavolo").fadeOut('500');
                        $("#rowNumeroPersone").fadeOut('500');
                        $("#rowCognome").fadeIn('500');
                    } else {
                        $("#rowNumeroTavolo").fadeIn('500');
                        $("#rowNumeroPersone").fadeIn('500');
                        $("#rowCognome").fadeOut('500');
                    }
                });
            }
        });
    }).fadeIn('500');
}

function confirmOrder(tavolo) {
    if (tavolo) {
        $.ajax({
            url: "controller/CarrelloController.php",
            method: "POST",
            data: {
                method: 'addToExistingOrder'
            },
            success: function (response) {
                location.reload();
            }
        });
    } else {
        var tavolo = null;
        var persone = null;
        var cognome = null;
        if (!($("#asporto").is(':checked'))) {
            tavolo = $("#numeroTavolo").val();
            persone = $("#numeroPersone").val();
        } else {
            cognome = $("#cognome").val();
            if (cognome == "") cognome = "x";
        }
        $.ajax({
            url: "controller/CarrelloController.php",
            method: "POST",
            data: {
                method: 'addNewOrder',
                tavolo: tavolo,
                coperti: persone,
                cognome: cognome
            },
            success: function (response) {
                if (!($("#asporto").is(':checked'))) {
                    location.reload();
                } else {
                    location.reload();
                }
            }
        });
    }
}

function showNote(product) {
    $("#modalNoteProductTitle").html(product);
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'getProductNote',
            whatProduct: product
        },
        success: function (response) {
            $("#valueNoteProduct").val(response);
            $("#modalNoteProduct").modal('toggle');
            $("#btnSaveNote").attr("nomeProdotto", product);
        }
    });
}

function saveNoteProduct() {
    var note = $("#valueNoteProduct").val();
    var product = $("#btnSaveNote").attr("nomeProdotto");
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'setProductNote',
            whatProduct: product,
            note: note
        },
        success: function (response) {
            $("#modalNoteProduct").modal('toggle');
        }
    });
}

function decreaseFromCart(product) {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'decreaseProduct',
            whatProduct: product
        },
        success: function (response) {
            var numProd = parseInt(response);
            if (numProd == 0) location.reload(); else orderResume();
        }
    });
}

function newOrder() {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'newOrder'
        },
        success: function (response) {
            getNumberProductInCart();
        }
    });
}

function addToCart(product, option) {
    $.ajax({
        url: "controller/CarrelloController.php",
        method: "POST",
        data: {
            method: 'addProduct',
            whatProduct: product
        },
        success: function (response) {
            if (option == "carrello") orderResume(); else getNumberProductInCart();
        }
    });
}

function printProduct(getProduct) {
    console.log(getProduct);
    $.ajax({
        url: "controller/ProdottoController.php",
        method: "POST",
        data: {
            method: 'getProduct',
            whatProduct: getProduct
        },
        success: function (response) {
            var ob = JSON.parse(response);
            $(getProduct).each(function () {
                var categ = this;
                $(ob[categ]).each(function () {
                    $("#table" + categ).append("<tr><td width=\"90%\">" + this.nome + "</td><td onclick=\"addToCart('" + this.nome + "', null);\"><i class=\"material-icons\" style=\"color: green;\">add</i></td></tr>");
                });
                $("#div" + categ).show();
            });
        }
    });
}

function changeCategory(cat, active) {
    $("#tableHeader td").removeClass("table-header-active");
    $("#bodyContent").hide().load("components/divAntipastiContorni.html", function () {
        if (cat == "antipasti") {
            var getProduct = ["antipasto", "contorno"];
        } else if (cat == "primiPiatti") {
            var getProduct = ["primo_di_pesce", "primo_di_carne"];
        } else if (cat == "secondiPiatti") {
            var getProduct = ["secondo_di_carne", "secondo_di_pesce"];
        } else if (cat == "bibite") {
            var getProduct = ["bibita"];
        }
        printProduct(getProduct);
        $(active).addClass("table-header-active");
    }).fadeIn('500');
}

function addBarra() {
    addToCart("barra", "carrello");
}

function scontrinoLibero() {
    $("#modalScontrinoLibero").modal('toggle');
}

$("#btnEseguiScontrinoLibero").click(function () {
    let descr = $("#descrizioneScontrinoLibero").val();
    let price = $("#prezzoScontrinoLibero").val();
    if (price != "") price = "$" + parseInt(price) * 100;
    else return;
    let cat = $("#categoriaScontrinoLibero").val();
    if (descr != "") descr = "(" + descr + ")/";
    let cf = $("#codiceFiscaleScontrinoLibero").val();
    let commands = ["=" + cat + "/" + descr + price];
    if (cf.length == 16) {
        commands.push("=\"/?C/(" + cf + ")");
    } else if (cf.length != 0) {
        return;
    }
    commands.push("=T1")
    $.ajax({
        type: 'POST',
        url: 'controller/CassaController.php',
        data : {
            method: 'commandsToPrint',
            command: commands
        },
        success: (response) => {
            location.reload();
        }
    })
});

function trasmettiChiusure() {
	$.ajax({
        type: 'POST',
        url: 'controller/CassaController.php',
        data : {
            method: 'commandsToPrint',
            command: ["=C422"]
        },
        success: (response) => {
            location.reload();
        }
    })
}

function pubblicaMessaggio() {
	$.ajax({
        type: 'POST',
        url: 'controller/CassaController.php',
        data : {
            method: 'commandsToPrint',
            command: ["=D1/(Seguici su instagram:)", "=D2/(@ristorante.europa)"]
        },
        success: (response) => {
            location.reload();
        }
    })
}

function menuDelGiorno() {
    $("#modalMenuGiorno").modal('toggle');
}

function saveMenu() {
    $.ajax({
        type: 'POST',
        url: 'controller/ProdottoController.php',
        data : {
            method: 'setMenu',
            fish: $("#fishMenu").val(),
            meat: $("#meatMenu").val()
        },
        success: (response) => {
            location.reload();
        }
    })
}
