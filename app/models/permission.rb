class Permission < ActiveRecord::Base
  has_and_belongs_to_many :users, inverse_of: :user_permissions
  has_and_belongs_to_many :groups, inverse_of: :permissions

  validates_presence_of :code_name, :description
end
