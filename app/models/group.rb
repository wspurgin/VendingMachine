class Group < ActiveRecord::Base
  has_many :users, inverse_of: :group
  has_and_belongs_to_many :permissions, inverse_of: :groups,
    join_table: :group_permissions

  validates_presence_of :name
end
