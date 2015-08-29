class AddUniqueIndexOnSmuIdOnUsers < ActiveRecord::Migration
  def change
    remove_index :users, name: :smu_id

    add_index :users, :smu_id, :unique => true
  end
end
