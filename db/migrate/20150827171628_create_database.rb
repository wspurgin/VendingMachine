class CreateDatabase < ActiveRecord::Migration
  def change
    create_table "Group_Permissions", id: false, force: :cascade do |t|
      t.integer "group_id",      limit: 4, default: 0, null: false
      t.integer "permission_id", limit: 4, default: 0, null: false
    end

    add_index "group_permissions", ["group_id"], name: "group_id", using: :btree
    add_index "group_permissions", ["permission_id"], name: "permission_id", using: :btree

    create_table "Groups", force: :cascade do |t|
      t.string "name", limit: 30, null: false
    end

    create_table "Logs", force: :cascade do |t|
      t.integer "user_id",        limit: 4, null: false
      t.integer "product_id",     limit: 4, null: false
      t.integer "machine_id",     limit: 4, null: false
      t.date    "date_purchased"
    end

    add_index "logs", ["machine_id"], name: "machine_id", using: :btree
    add_index "logs", ["product_id"], name: "product_id", using: :btree
    add_index "logs", ["user_id"], name: "user_id", using: :btree

    create_table "Machine_Supplies", id: false, force: :cascade do |t|
      t.integer "machine_id", limit: 4,             null: false
      t.integer "product_id", limit: 4,             null: false
      t.integer "quantity",   limit: 4, default: 0, null: false
    end

    add_index "machine_supplies", ["machine_id"], name: "machine_id", using: :btree
    add_index "machine_supplies", ["product_id"], name: "product_id", using: :btree

    create_table "Machines", force: :cascade do |t|
      t.string "machine_location", limit: 30, null: false
    end

    create_table "Permissions", force: :cascade do |t|
      t.string "description", limit: 80, null: false
      t.string "code_name",   limit: 20, null: false
    end

    create_table "Products", force: :cascade do |t|
      t.string "sku",    limit: 128, default: "",  null: false
      t.string "name",   limit: 50,                null: false
      t.string "vendor", limit: 30,                null: false
      t.float  "cost",   limit: 53,  default: 0.0, null: false
    end

    create_table "Team_Members", id: false, force: :cascade do |t|
      t.integer "team_id", limit: 4, default: 0, null: false
      t.integer "user_id", limit: 4, default: 0, null: false
    end

    add_index "team_members", ["team_id"], name: "team_id", using: :btree
    add_index "team_members", ["user_id"], name: "user_id", using: :btree

    create_table "Teams", force: :cascade do |t|
      t.string "team_name",       limit: 30,               null: false
      t.string "class",           limit: 30,               null: false
      t.date   "expiration_date",                          null: false
      t.float  "team_balance",    limit: 53, default: 0.0, null: false
    end

    create_table "User_Permissions", id: false, force: :cascade do |t|
      t.integer "user_id",       limit: 4, default: 0, null: false
      t.integer "permission_id", limit: 4, default: 0, null: false
    end

    add_index "user_permissions", ["permission_id"], name: "permission_id", using: :btree
    add_index "user_permissions", ["user_id"], name: "user_id", using: :btree

    create_table "Users", force: :cascade do |t|
      t.string  "password", limit: 128,               null: false
      t.string  "name",     limit: 30,                null: false
      t.string  "email",    limit: 64,                null: false
      t.integer "group_id", limit: 4,                 null: false
      t.float   "balance",  limit: 53,  default: 0.0, null: false
    end

    add_index "users", ["group_id"], name: "group", using: :btree

    add_foreign_key "Group_Permissions", "Groups", column: "group_id", name: "group_permission_fk_group", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Group_Permissions", "Permissions", column: "permission_id", name: "group_permission_fk_permission", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Logs", "Machines", column: "machine_id", name: "log_fk_machine", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Logs", "Products", column: "product_id", name: "log_fk_product", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Logs", "Users", column: "user_id", name: "log_fk_user", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Machine_Supplies", "Machines", column: "machine_id", name: "machine_supplies_fk_machine", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Machine_Supplies", "Products", column: "product_id", name: "machine_supplies_fk_product", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Team_Members", "Teams", column: "team_id", name: "team_members_fk_team", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Team_Members", "Users", column: "user_id", name: "team_members_fk_user", on_update: :cascade, on_delete: :cascade
    add_foreign_key "User_Permissions", "Permissions", column: "permission_id", name: "user_permission_fk_permission", on_update: :cascade, on_delete: :cascade
    add_foreign_key "User_Permissions", "Users", column: "user_id", name: "user_permission_fk_group", on_update: :cascade, on_delete: :cascade
    add_foreign_key "Users", "Groups", column: "group_id", name: "user_fk_group", on_update: :cascade, on_delete: :cascade
  end
end
