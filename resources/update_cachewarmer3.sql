ALTER TABLE `url_by_protocol`
ADD	 `url_by_protocol_last_updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this entry was last updated';

ALTER TABLE `urls`
ADD	 `url_domain_priority` tinyint(3) unsigned NOT NULL COMMENT 'The priority level of the domain (lower is more important)'
	,INDEX `IND_url_domain_priority` ( `url_domain_priority` ) 
	,INDEX `IND_url_depth__domain_priority` ( `url_depth`,`url_domain_priority` ); 


