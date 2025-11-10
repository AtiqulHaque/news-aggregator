# How it works

1. Scheduler runs every minute
2. Command finds active campaigns For each campaign, gets active sources
3. Dispatches CrawlCampaignJob to crawling queue 
5. Job crawls source and extracts articles 
6. Articles saved to database 
7. IndexArticleToElasticsearch dispatched to elasticsearch queue 
8. Articles indexed to Elasticsearch

### Next steps1. Start the scheduler: ``bash
