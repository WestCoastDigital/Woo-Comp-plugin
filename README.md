# WooCommerce Competition Plugin
WooCommerce plugin that allows to automatically enter orders into a draw and allocate ticket quantities based on checkout value

## Usage
All pages are located under the WooCommerce admin menu, Competition Settings and Competition Entries

### Settings
![Settings Options](https://github.com/WestCoastDigital/Woo-Comp-plugin/blob/main/assets/img/settings.png?raw=true)

1. Set start and end date
1. Set competition values, how many entries for spend value screenshot example spend 20 - 49 get 1 entry, 50 - 99 get 6 and 100+ get 14
1. Enter ticket start number. It will generate a unique number between 1000 and 9999 but you can also prefix it with your own number
1. Exclude Roles allows you to set specific roles that are not valid to enter
1. Lock Winner means that once you choose a winner it can not be undone unless toggled off or entries are reset

### Emails
![Email Options](https://github.com/WestCoastDigital/Woo-Comp-plugin/blob/main/assets/img/emails.png?raw=true)

1. Emails are auto generated and by default the heading is "Your [Site Name] Competition Ticket Numbers" and the default body is to email out the ticket numbers as a table<table style="width:100%"><tr><td style="width:50%">Ticket Number</td><td style="width:50%">1234</td></tr></table>
1. Use WYSIWYG to override

### T&Cs
![Terms Options](https://github.com/WestCoastDigital/Woo-Comp-plugin/blob/main/assets/img/terms.png?raw=true)

1. Create your competition terms and conditions and use shortcode on any page

### Entries
![Entries View](https://github.com/WestCoastDigital/Woo-Comp-plugin/blob/main/assets/img/entries.png?raw=true)

![Pick Winner](https://github.com/WestCoastDigital/Woo-Comp-plugin/blob/main/assets/img/confetti.png?raw=true)

1. Download as CSV to save to computer
1. Pick Winner to randomly choose your winner, it displays in gold text and when you pick it also rains confetti so you can live stream if you choose. If you have it locked in the settings page the button will grey out and you cannot pick another winner.
1. Reset Entries allows you to delete all entries, so download CSV first, and the chosen winner. As this cannot be undone it will give you a warning first.
1. You can also see how many tickets and how many orders were made

## Checkout
In the checkout screen it shows how many tickets the customer will receive based on their order, unless they have not got enough in their cart based on the minimum settings, or if their role has been excluded in the settings.