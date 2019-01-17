# The Task
Create a REST API that would be able to manipulate store branches in a tree data structure that can have infinite level.
Store branches can have any number of other store branches (children).

# Choosing the data structure

On first glance, a tree data structure can be implemented relaltively easily with table structure like this:

| id | name    | parent_id |
|----|---------|-----------|
| 1  | Store 1 | null      |
| 2  | Store 2 | 1         |
| 3  | Store 3 | 2         |
| 4  | Store 4 | 1         |
| 5  | Store 5 | 4         |

This is what is called **Adjacency List Model**. Each node references its immediate parent node, except for the root node pointing to null. One advantage with this model is that, when moving a sub tree to a differnet parent, it's as simple as changing the parent_id of the top node of the sub stree to point to the new parent. There is no hard limit on the number of level allowed. However, to load a complete tree with very deep level, it needs to execute many database queries to achieve that. Specifically, the number of queries grow linearly with the number of levels.


A bit googling came back with an alternative solution for the task -**Nested Set Model**

| id | name    | left | right |
|----|---------|-----|-----|
| 1  | Store 1 | 1   | 10  |
| 2  | Store 2 | 2   | 5   |
| 3  | Store 3 | 3   | 4   |
| 4  | Store 4 | 6   | 9   |
| 5  | Store 5 | 7   | 8   |

Instead of referencing the parent explicitly from a child, in Nested Set Model, each node represent a range that it occupies, including all of its child nodes. The range is defined by left and right numeric values. In the example the root node has the range 1~10, which covers all of its children's ranges. The main advantage of this model is that, the whole tree can be fetched with just one query, by selecting all the records that have range within the root node's left and right values. The downside of this structure is also apparent in that, insert and update operations are more complex to implement.

Being more suitable for the requirement, and also as a challenge to myself, the Nested Set model was chosen for the task.


# Reference
* http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
* https://en.wikipedia.org/wiki/Nested_set_model
