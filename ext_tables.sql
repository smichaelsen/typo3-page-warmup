CREATE TABLE tx_pagewarmup_reservation (
	cache tinytext NOT NULL,
	url text NOT NULL,
	cache_tag tinytext NOT NULL,
	KEY cache_tag (cache(191),cache_tag(191))
);

CREATE TABLE tx_pagewarmup_queue (
	url text NOT NULL,
	done tinyint(4) DEFAULT '0' NOT NULL,
	queued int(11) DEFAULT '0' NOT NULL,
	UNIQUE url (url(191))
);
