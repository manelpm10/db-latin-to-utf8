Translate latin1 encoding to UTF8
=================================

Shity but useful script to translate a the database encoding for a field in a safe way.

Modify the script.php file and replace the next params.

* \<DB_HOST\>
* \<DB_USER\>
* \<DB_PASS\>
* \<DB_SCHEMA\>
* \<FIELD_TO_TRANSLATE\>
* \<TABLE_TO_TRANSLATE\>

This script helps you in the step 3 of the next toolbox for sleuthing character encoding problems.

A toolbox for sleuthing character encoding problems:
====================================================

1 - Use MySQL CHAR_LENGTH to find rows with multi-byte characters:

```SQL
SELECT name FROM clients WHERE LENGTH(name) != CHAR_LENGTH(name);
```

2 - Use MySQL HEX and PHP bin2hex

```SQL
SELECT name, HEX(name) FROM clients;
```

Get the result back into PHP, and run a bin2hex on the string, compare it to MySQL's hex of that same string

3 - See it in both encodings

```PHP
$db->query("SET NAMES latin1");
$db->query("SELECT name, HEX(name) FROM clients");
(compare the string and its hex result from MySQL with the bin2hex from PHP)
$db->query("SET NAMES utf8");
$db->query("SELECT name, HEX(name) FROM clients");
```

For all those strings that looked perfect in LATIN1 encoding, here's how I would fix them in the database:

```PHP
$db->query("SET NAMES latin1");
$db->query("SELECT id, name FROM clients");
$hex = bin2hex($x['name']);
$db->query("SET NAMES utf8");
$db->query("UPDATE clients SET name=UNHEX($hex) WHERE id=$id")
```

That seemed to work, for most things. If you have some encoding in latin1 and some in other encodings, continue reading.

4 - Use a HEX/UNHEX replace for the unfixable charactgers

Imagine, after all that fixing, you found strings like this:

```TXT
Let~!@s say ^|%What a nice house you~!@ve got here, don~!@t you think?^!%.
```

Who knows when or how this happened, but obviously ~!@ is meant to be an apostrophe, ^|% an open-quote, and ^!% a closing-quote.

I'd use MySQL SUBSTRING to find the 3 characters that needed replacing:

```SQL
SELECT SUBSTRING(quote, 353, 3) FROM table WHERE id=1;
```

Once narrowing it down to the exact string, add a HEX() around it:

```SQL
SELECT HEX(SUBSTRING(quote, 353, 3)) FROM table WHERE id=1;
```

... which would give you a result like C8035EF6BB92BF2

Then use that with MySQL REGEXP to find and replace all occurences in your database!

```SQL
UPDATE table SET field = REPLACE(field, UNHEX('C8035EF6BB92BF2'), "'") WHERE field REGEXP UNHEX('C8035EF6BB92BF2');
```

I set up some PHP arrays of all my tables, and all their text fields, to run this same query on everything in my database.

Then do it again for curly-quotes and other weirnesses.

A few times, I had no idea what a character was supposed to be (like the Icelandic and Gaelic ones) - so I had to go visit the artist's website, and find their song titles or bio information spelled correctly there.

5 - Validate UTF8

Got the is_utf8 function from PHP docs to validate all the values in the database.
Doing this found a bunch of invisible problems, which only through hours of MySQL SUBSTRING and HEX revealed that there were invisible characters with HEX values of 00-19 scattered around my text fields.
I used the same solution as above to replace them:

```SQL
UPDATE table SET field = REPLACE(field, UNHEX('05'), '') WHERE field REGEXP UNHEX('05');
```

I looped this inside an array of all hex values under 20.

6 - Converting HTML entities

Find HTML entities hidden in the database:

```SQL
SELECT field FROM table WHERE field REGEXP '&#[0-9]*;'
```

Use the utf8_chr function from the comments of the PHP html_entity_decode page.
Use PHP preg_match_all to find the entities inside the string, and replace them:

```PHP
function myreplace($string) {
    preg_match_all('/&#(\d*)/', $string, $matches);
    foreach($matches[1] as $num) {
        $string = str_replace("&#$num;", utf8_chr($num), $string);
    }

    return $string;
}
```

Update the database with the returned result.

Related documents:

* http://archive.oreilly.com/pub/post/turning_mysql_data_in_latin1_t.html
