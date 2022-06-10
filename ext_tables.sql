CREATE TABLE tx_pagewarmup_reservation (
	cache tinytext,
	url text,
	cache_tag tinytext,
	KEY cache_tag (cache,cache_tag)
);

CREATE TABLE tx_pagewarmup_queue (
	url text,
	UNIQUE url (url),
);
