create table pharmad.options
(
    id          bigint auto_increment primary key,
    grupa       varchar(191) null,
    ime_grupe   varchar(191) null,
    title       varchar(191) not null,
    description text null,
    type        varchar(191) null,
    value       varchar(191) null,
    value_opt   varchar(191) null,
    option_sku  varchar(191) null,
    `data`      text null,
    sort_order  int unsigned default 0 null,
    status      tinyint null,
    created_at  timestamp null,
    updated_at  timestamp null
) collate = utf8mb4_general_ci;

--
create table pharmad.product_option
(
    id         bigint unsigned auto_increment primary key,
    product_id bigint unsigned not null,
    option_id  bigint unsigned not null,
    image_id   bigint unsigned default 0 not null,
    sku        varchar(191)      not null,
    parent     varchar(191)      not null,
    parent_id  bigint unsigned not null,
    quantity   int unsigned default 0 not null,
    price      decimal(15, 4)    not null,
    `data`     text null,
    status     tinyint default 0 not null,
    created_at timestamp null,
    updated_at timestamp null
) collate = utf8mb4_general_ci;