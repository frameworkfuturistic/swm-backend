    //  GET /api/entities/search?
    //  Multiple filter combinations
    //  search=john                           // Partial text search
    //  ulb_id=1                              // Filter by ULB
    //  cluster_id=2                          // Filter by Cluster
    //  is_active=true                        // Boolean filter
    //  usage_type=Commercial                 // Enum filter
    //  start_date=2023-01-01&end_date=2023-12-31 // Date range
    //  min_monthly_demand=100&max_monthly_demand=1000 // Numeric range
    //  sort_by=monthly_demand&sort_direction=asc // Sorting
    //  per_page=20                           // Pagination
    
URL
---
GET /api/entities/search?search=john&ulb_id=1&cluster_id=2&is_active=true&usage_type=Commercial&start_date=2023-01-01&end_date=2023-12-31&min_monthly_demand=100&max_monthly_demand=1000&sort_by=monthly_demand&sort_direction=asc&per_page=20


## Explanation of Query Parameters
- search=john: Performs a partial text search for "john".
- ulb_id=1: Filters results to include only entities belonging to ULB ID 1.
- cluster_id=2: Filters results to include only entities in Cluster ID 2.
- is_active=true: Filters results to include only active entities.
- usage_type=Commercial: Filters results to include only entities of - type "Commercial".
- start_date=2023-01-01&end_date=2023-12-31: Filters results for inclusion or verification dates within the specified range.
- min_monthly_demand=100&max_monthly_demand=1000: Filters entities with a monthly demand between 100 and 1000.
- sort_by=monthly_demand&sort_direction=asc: Sorts the results by monthly_demand in ascending order.
- per_page=20: Limits the number of results per page to 20.

