class User < ActiveRecord::Base
  belongs_to :group, inverse_of: :users
  has_and_belongs_to_many :user_permissions,
    class_name: "Permission",
    inverse_of: :users,
    join_table: :user_permissions

  has_secure_password
  validates_presence_of :group, :name, :email, :smu_id
  validates_uniqueness_of :smu_id
  validates :password, length: { minimum: 8 }

  scope :with_smu_id, -> (id) { find_by!(smu_id: id) }

  def permissions
    if changed? || group.changed?
      @permissions = all_unique_permissions
    end
    @permissions ||= all_unique_permissions
  end

  private

  def all_unique_permissions
    (user_permissions + group.permissions).uniq
  end
end
