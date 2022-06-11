CREATE TABLE tx_pagewarmup_reservation (
	cache tinytext,
	url text,
	cache_tag tinytext,
	KEY cache_tag (cache,cache_tag)
);

CREATE TABLE tx_pagewarmup_queue (
	url text,
	done tinyint(4) DEFAULT '0' NOT NULL,
	UNIQUE url (url),
);
