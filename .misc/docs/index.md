## Opis tabel

`bookshop_order` - zawiera dane koszyków (zamówień) użytkowników

| Kolumna            | Opis                                                                                |
|:-------------------|:------------------------------------------------------------------------------------|
| user\_id           | identyfikator użytkownika; jeden użytkownik może posiadać wiele koszyków / zamówień |
| items\_total       | wartość wszystkich produktów w koszyku                                              |
| adjustments\_total | wartość wszystkich dodatkowych dopłat i upustów (wartość ujemna) do zamówienia      |
| total              | wartość całego zamówienia do zapłaty (suma `items_total` i `adjustments_total`)     |

`bookshop_order_item` - zawiera części składowe zamówień (przedmioty zamówień)

| Kolumna     | Opis                                                                   |
|:------------|:-----------------------------------------------------------------------|
| order\_id   | identyfikator zamówienia, do którego przypisany jest dany przedmiot    |
| product\_id | identyfikator produktu, który jest przypisany do przedmiotu zamówienia |
| quantity    | liczba sztuk                                                           |
| unit\_price | cena jednostkowa (za sztukę) przedmiotu                                |
| total       | wartość przedmiotu zamówienia, którą powinien zapłacić klient          |
| tax\_value  | wartość podatku dla danego przedmiotu zamówienia                       |

`bookshop_product` - zawiera informacje o produktach dostępnych do zakupu 

| Kolumna   | Opis                                                                             |
|:----------|:---------------------------------------------------------------------------------|
| name      | nazwa produktu                                                                   |
| code      | unikalny kod produktu                                                            |
| type      | typ produktu (`book` - książka; `audio` - produkt cyfrowy; `course` - szkolenie) |
| price     | cena bazowa produktu za sztukę                                                   |
| tax\_rate | stawka podatku podana w procentach (`null` oznacza produkt zwolniony z podatku)  |

## Dokumentacja endpointu

W [pliku](contract.yaml) znajduje się dokumentacja w formacie OpenApi, zawierająca kontrakt dla endpointu do przygotowania. Dokumentację należy uzupełnić o adres do endpointu. Zakładamy pracę w architekturze REST. 
